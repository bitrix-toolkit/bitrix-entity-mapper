<?php

namespace BitrixToolkit\BitrixEntityMapper\Query;

use _CIBElement;
use CIBlockElement;
use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use Generator;
use InvalidArgumentException;
use Iterator;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Field;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use BitrixToolkit\BitrixEntityMapper\Map\PropertyMap;

class Select implements Iterator
{
    protected $entityMap;
    protected $whereRaw = [];
    protected $where = [];
    protected $orderBy = [];
    protected $generator;

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
        $filterBuilder = new FilterBuilder($this->entityMap, $this->where, $this->whereRaw);

        $filter = $filterBuilder->getFilter();
        $order = $this->getOrderingRules();

        $rs = CIBlockElement::GetList($order, $filter);
        while ($element = $rs->GetNextElement()) {
            if ($element instanceof _CIBElement) {
                $data = array_merge($this->getFieldsData($element), $this->getPropertiesData($element));
                $elementFields = $element->GetFields();
                yield new RawResult($elementFields['ID'], $elementFields['IBLOCK_ID'], $data);
            }
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
        if ($this->generator instanceof Generator) {
            $this->generator->next();
        } else {
            $this->generator = $this->iterator();
        }

        return $this->generator->current();
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

            $data[$field->getCode()] = RawResult::normalizeValue($field, $elementFields[$key]);
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
            $data[$property->getCode()] = RawResult::normalizePropertyValue($property, $rawValue);
        }

        return $data;
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

    /**
     * Вернуть текущий объект.
     *
     * @return object
     * @throws ReflectionException
     */
    public function current()
    {
        $this->generator = isset($this->generator) ? $this->generator : $this->iterator();
        return $this->generator->current();
    }

    /**
     * Переместить курсор к следующему объекту.
     *
     * @throws ReflectionException
     */
    public function next()
    {
        $this->generator = isset($this->generator) ? $this->generator : $this->iterator();
        $this->generator->next();
    }

    /**
     * Вернуть индекс текущего объекта.
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function key()
    {
        $this->generator = isset($this->generator) ? $this->generator : $this->iterator();
        return $this->generator->key();
    }

    /**
     * Проверить текущую позицию курсора.
     *
     * @return bool
     * @throws ReflectionException
     */
    public function valid()
    {
        $this->generator = isset($this->generator) ? $this->generator : $this->iterator();
        return $this->generator->valid();
    }

    /**
     * Начать новую итерацию.
     *
     * @throws ReflectionException
     */
    public function rewind()
    {
        $this->generator = $this->iterator();
    }
}