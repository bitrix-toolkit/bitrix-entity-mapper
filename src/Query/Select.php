<?php

namespace Sheerockoff\BitrixEntityMapper\Query;

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
    protected $where = [];
    protected $orderBy = [];

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
     * @param string $p
     * @param string $direction
     * @return $this
     */
    public function orderBy($p, $direction = 'asc')
    {
        $this->orderBy[] = [$p, $direction];
        return $this;
    }

    /**
     * @return Generator
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function iterator()
    {
        $infoBlockType = $this->entityMap->getAnnotation()->getType();
        $infoBlockCode = $this->entityMap->getAnnotation()->getCode();
        $infoBlock = CIBlock::GetList(null, [
            'TYPE' => $infoBlockType,
            'CODE' => $infoBlockCode,
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        self::assert(!empty($infoBlock['ID']), "Инфоблок с кодом $infoBlockCode и типом $infoBlockType не найден.");

        $filter = ['IBLOCK_ID' => $infoBlock['ID']];
        foreach ($this->where as $entry) {
            list($property, $operator, $value) = $entry;
            $filter += $this->getFilterRow($property, $operator, $value);
        }

        /** @var PropertyMap[] $fields */
        $fields = array_filter($this->entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Field;
        });

        /** @var PropertyMap[] $properties */
        $properties = array_filter($this->entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Property;
        });

        $classRef = new ReflectionClass($this->entityMap->getClass());

        $rs = CIBlockElement::GetList(null, $filter);
        while ($element = $rs->GetNextElement()) {
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

            $elementProperties = $element->GetProperties();
            foreach ($properties as $property) {
                $key = $property->getAnnotation()->getCode();
                self::assert(
                    array_key_exists($key, $elementProperties) && array_key_exists('VALUE', $elementProperties[$key]),
                    "Свойство $key не найдено в результатах CIBlockElement::GetList()."
                );

                $data[$property->getCode()] = self::normalizeValue(
                    $elementProperties[$key]['VALUE'],
                    $property->getAnnotation()->getType()
                );
            }

            $object = $classRef->newInstance();
            yield self::hydrate($object, $data);
        }
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
     * @param string $property
     * @param string $operator
     * @param mixed $value
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getFilterRow($property, $operator, $value)
    {
        $propertyMap = $this->entityMap->getProperty($property);
        $propertyAnnotation = $propertyMap->getAnnotation();

        if ($propertyAnnotation instanceof Field) {
            $k = $operator . $propertyAnnotation->getCode();
            if ($propertyAnnotation->getType() === PropertyAnnotationInterface::TYPE_BOOLEAN) {
                $v = $value && $value !== 'N' ? 'Y' : 'N';
            } else {
                $v = $value;
            }
        } elseif ($propertyAnnotation instanceof Property) {
            if ($propertyAnnotation->getType() === PropertyAnnotationInterface::TYPE_BOOLEAN) {
                $k = $operator . 'PROPERTY_' . $propertyAnnotation->getCode() . '_VALUE';
            } else {
                $k = $operator . 'PROPERTY_' . $propertyAnnotation->getCode();
            }

            if ($propertyAnnotation->getType() === PropertyAnnotationInterface::TYPE_BOOLEAN) {
                $v = $value && $value !== 'N' ? 'Y' : false;
            } else {
                $v = $value;
            }
        } else {
            throw new InvalidArgumentException(
                'У свойства ' . $property . ' нет аннотаций  @' . Field::class .
                ' или @' . Property::class . '. Фильтрация по этому свойству невозможна.'
            );
        }

        return [$k => $v];
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return mixed
     * @throws Exception
     */
    protected static function normalizeValue($value, $type)
    {
        if ($value === null) {
            return null;
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