<?php

namespace Sheerockoff\BitrixEntityMapper\Query;

use _CIBElement;
use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CIBlock;
use CIBlockElement;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use Generator;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\Map\PropertyMap;

class Select
{
    protected $entityMap;
    protected $whereRaw = [];
    protected $where = [];
    protected $orderBy = [];

    /** @var Generator|null */
    protected $iterator;

    /**
     * Select constructor.
     * @param string $class
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function __construct($class)
    {
        $this->entityMap = EntityMap::fromClass($class);
    }

    /**
     * @param string $class
     * @return Select
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public static function from($class)
    {
        return new self($class);
    }

    /**
     * Может быть вызвано с двумя или тремя аргументами.
     *
     * Если 2 аргумента, то название свойства и значение для фильтрации.
     * Например: $this->where('name', 'bender');
     * По-умолчанию будет использован оператор сравнения "=".
     *
     * Если 3 аргумента, то название свойства, оператор сравнения и значение для фильтрации.
     * Например: $this->where('age', '>', 18);
     *
     * @param string $p Название свойства класса для фильтрации.
     * @param mixed $_ Если 3 аргумента то оператор сравнения, иначе значение для фильтрации.
     * @param mixed Если 3 аргумента то значение для фильтрации.
     * @return $this
     */
    public function where($p, $_)
    {
        if (func_num_args() > 2) {
            $property = $p;
            $operator = $_;
            $value = func_get_arg(2);
        } else {
            $property = $p;
            $operator = '=';
            $value = $_;
        }

        $this->where[] = [$property, $operator, $value];
        return $this;
    }

    /**
     * @param string $f
     * @param mixed $_
     * @param mixed
     * @return $this
     */
    public function whereRaw($f, $_)
    {
        if (func_num_args() > 2) {
            $field = $f;
            $operator = $_;
            $value = func_get_arg(2);
        } else {
            $field = $f;
            $operator = '=';
            $value = $_;
        }

        $this->whereRaw[$operator . $field] = $value;
        return $this;
    }

    /**
     * @param string $p
     * @param string $d
     * @return $this
     */
    public function orderBy($p, $d = 'asc')
    {
        $this->orderBy[$p] = $d;
        return $this;
    }

    /**
     * @return Generator|RawResult[]
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function rawIterator()
    {
        $filter = $this->getInfoBlockFilter();

        foreach ($this->where as $entry) {
            list($property, $operator, $value) = $entry;
            $filter += $this->getFilterRow($property, $operator, $value);
        }

        $filter = array_merge($filter, $this->whereRaw);
        $order = $this->getOrderingRules();

        $rs = CIBlockElement::GetList($order, $filter);
        while ($element = $rs->GetNextElement()) {
            $data = array_merge($this->getFieldsData($element), $this->getPropertiesData($element));
            $elementFields = $element->GetFields();
            yield new RawResult($elementFields['ID'], $elementFields['IBLOCK_ID'], $data);
        }
    }

    /**
     * @return Generator
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function iterator()
    {
        $classRef = new ReflectionClass($this->entityMap->getClass());
        foreach ($this->rawIterator() as $rawResult) {
            $object = $classRef->newInstanceWithoutConstructor();
            yield self::hydrate($object, $rawResult->getData());
        }
    }

    /**
     * @return object
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function fetch()
    {
        if (!isset($this->iterator)) {
            $this->iterator = $this->iterator();
        } else {
            $this->iterator->next();
        }

        return $this->iterator->current();
    }

    /**
     * @return object[]
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function fetchAll()
    {
        $array = [];
        foreach ($this->iterator() as $object) {
            $array[] = $object;
        }

        return $array;
    }

    /**
     * @param mixed $term
     * @param string $msg
     */
    protected static function assert($term, $msg)
    {
        if (!$term) {
            throw new InvalidArgumentException($msg);
        }
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getInfoBlockFilter()
    {
        $infoBlockType = $this->entityMap->getAnnotation()->getType();
        $infoBlockCode = $this->entityMap->getAnnotation()->getCode();
        $infoBlock = CIBlock::GetList(null, [
            'TYPE' => $infoBlockType,
            'CODE' => $infoBlockCode,
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        self::assert(!empty($infoBlock['ID']), "Инфоблок с кодом $infoBlockCode и типом $infoBlockType не найден.");

        return ['=IBLOCK_ID' => $infoBlock['ID']];
    }

    /**
     * @param string $property
     * @param string $operator
     * @param mixed $value
     * @return array|null
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function getFilterRow($property, $operator, $value)
    {
        $propertyMap = $this->entityMap->getProperty($property);
        $propertyAnnotation = $propertyMap->getAnnotation();

        $k = null;
        $v = null;

        if ($propertyAnnotation instanceof Field) {
            $k = $operator . $propertyAnnotation->getCode();
            if ($propertyAnnotation->getType() === Field::TYPE_BOOLEAN) {
                $v = $value && $value !== 'N' ? 'Y' : 'N';
            } else {
                $v = $value !== '' && $value !== null ? $value : false;
            }
        } elseif ($propertyAnnotation instanceof Property) {
            if ($propertyAnnotation->getType() === Property::TYPE_BOOLEAN) {
                $k = $operator . 'PROPERTY_' . $propertyAnnotation->getCode() . '_VALUE';
            } else {
                $k = $operator . 'PROPERTY_' . $propertyAnnotation->getCode();
            }

            if ($propertyAnnotation->getType() === Property::TYPE_BOOLEAN) {
                $v = $value && $value !== 'N' ? 'Y' : false;
            } elseif ($propertyAnnotation->getType() === Property::TYPE_DATETIME) {
                if (!$value) {
                    $v = false;
                } elseif ($value instanceof DateTime) {
                    $v = $value->format('Y-m-d H:i:s');
                } elseif ($value instanceof BitrixDateTime) {
                    $v = $value->format('Y-m-d H:i:s');
                } else {
                    $v = (new DateTime($value))->format('Y-m-d H:i:s');
                }
            } else {
                $v = $value !== '' && $value !== null ? $value : false;
            }
        }

        return $k ? [$k => $v] : null;
    }

    /**
     * @return array
     */
    protected function getOrderingRules()
    {
        $order = [];
        foreach ($this->orderBy as $property => $direction) {
            $propertyAnnotation = $this->entityMap->getProperty($property)->getAnnotation();
            if ($propertyAnnotation instanceof Field) {
                $order[$propertyAnnotation->getCode()] = $direction;
            } elseif ($propertyAnnotation instanceof Property) {
                $order['PROPERTY_' . $propertyAnnotation->getCode()] = $direction;
            }
        }

        return $order;
    }

    /**
     * @param _CIBElement $element
     * @return array
     * @throws Exception
     */
    protected function getFieldsData(_CIBElement $element)
    {
        /** @var PropertyMap[] $fields */
        $fields = array_filter($this->entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Field;
        });

        $data = [];
        $elementFields = $element->GetFields();
        foreach ($fields as $field) {
            $key = $field->getAnnotation()->getCode();
            self::assert(
                array_key_exists($key, $elementFields),
                "Поле $key не найдено в результатах CIBlockElement::GetList()."
            );

            $data[$field->getCode()] = self::normalizeValue(
                $elementFields[$key],
                $field->getAnnotation()->getType()
            );
        }

        return $data;
    }

    /**
     * @param _CIBElement $element
     * @return array
     * @throws Exception
     */
    protected function getPropertiesData(_CIBElement $element)
    {
        /** @var PropertyMap[] $properties */
        $properties = array_filter($this->entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Property;
        });

        $data = [];
        $elementProperties = $element->GetProperties();
        foreach ($properties as $property) {
            $key = $property->getAnnotation()->getCode();
            self::assert(
                array_key_exists($key, $elementProperties) && array_key_exists('VALUE', $elementProperties[$key]),
                "Свойство $key не найдено в результатах CIBlockElement::GetList()."
            );

            $rawValue = $elementProperties[$key]['VALUE'];
            $data[$property->getCode()] = self::normalizePropertyValue($property, $rawValue);
        }

        return $data;
    }

    /**
     * @param PropertyMap $property
     * @param mixed $rawValue
     * @return mixed
     * @throws Exception
     */
    protected static function normalizePropertyValue(PropertyMap $property, $rawValue)
    {
        if ($property->getAnnotation()->isMultiple()) {
            return array_map(function ($value) use ($property) {
                return self::normalizeValue(
                    $value,
                    $property->getAnnotation()->getType(),
                    $property->getAnnotation()->getEntity()
                );
            }, is_array($rawValue) ? $rawValue : []);
        } else {
            return self::normalizeValue(
                $rawValue,
                $property->getAnnotation()->getType(),
                $property->getAnnotation()->getEntity()
            );
        }
    }

    /**
     * @param mixed $value
     * @param string $type
     * @param string|null $entity
     * @return mixed
     * @throws Exception
     */
    protected static function normalizeValue($value, $type, $entity = null)
    {
        if ($value === null) {
            return null;
        } elseif ($type === PropertyAnnotationInterface::TYPE_ENTITY) {
            return $value ? self::from($entity)->whereRaw('ID', $value)->fetch() : null;
        } elseif ($type === PropertyAnnotationInterface::TYPE_BOOLEAN) {
            return $value && $value !== 'N' ? true : false;
        } elseif (in_array($type, [PropertyAnnotationInterface::TYPE_INTEGER, PropertyAnnotationInterface::TYPE_FLOAT])) {
            return $value + 0;
        } elseif ($type === PropertyAnnotationInterface::TYPE_DATETIME) {
            return new DateTime($value);
        } else {
            return $value;
        }
    }

    /**
     * @param object $object
     * @param array $data
     * @return object
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    protected static function hydrate($object, array $data)
    {
        self::assert(is_object($object), 'Аргумент $object не является объектом.');
        $objectRef = new ReflectionObject($object);
        foreach ($data as $key => $value) {
            $propRef = $objectRef->getProperty($key);
            if (!$propRef->isPublic()) {
                $propRef->setAccessible(true);
                $propRef->setValue($object, $value);
                $propRef->setAccessible(false);
            } else {
                $propRef->setValue($object, $value);
            }
        }

        return $object;
    }
}