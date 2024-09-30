<?php

namespace BitrixToolkit\BitrixEntityMapper\Query;

use DateTime;
use Exception;
use InvalidArgumentException;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;
use BitrixToolkit\BitrixEntityMapper\Map\PropertyMap;

class RawResult
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $infoBlockId;

    /** @var array */
    protected $data = [];

    /**
     * RawResult constructor.
     * @param int $id
     * @param int $infoBlockId
     * @param array $data
     */
    public function __construct($id, $infoBlockId, array $data)
    {
        $this->id = $id;
        $this->infoBlockId = $infoBlockId;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getInfoBlockId()
    {
        return $this->infoBlockId;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $code
     * @return mixed
     */
    public function getField($code)
    {
        if (!array_key_exists($code, $this->data)) {
            throw new InvalidArgumentException("Поле $code не найдено в массиве данных.");
        }

        return $this->data[$code];
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
     * @param PropertyMap $property
     * @param mixed $rawValue
     * @return mixed
     * @throws Exception
     */
    public static function normalizePropertyValue(PropertyMap $property, $rawValue)
    {
        if ($property->getAnnotation()->isMultiple()) {
            return array_map(function ($value) use ($property) {
                return self::normalizeValue($property, $value);
            }, is_array($rawValue) ? $rawValue : []);
        } else {
            return self::normalizeValue($property, $rawValue);
        }
    }

    /**
     * @param PropertyMap $property
     * @param mixed $rawValue
     * @return mixed
     * @throws Exception
     */
    public static function normalizeValue(PropertyMap $property, $rawValue)
    {
        if ($rawValue === null) {
            return null;
        }

        $type = $property->getAnnotation()->getType();

        $map = [
            Property::TYPE_ENTITY => function ($value) use ($property) {
                $entity = $property->getAnnotation()->getEntity();
                return $value ? Select::from($entity)->whereRaw('ID', $value)->fetch() : null;
            },
            Property::TYPE_BOOLEAN => [self::class, 'normalizeBooleanValue'],
            Property::TYPE_INTEGER => [self::class, 'normalizeNumericValue'],
            Property::TYPE_FLOAT => [self::class, 'normalizeNumericValue'],
            Property::TYPE_DATETIME => [self::class, 'normalizeDateTimeValue'],
        ];

        return array_key_exists($type, $map) ? call_user_func($map[$type], $rawValue) : $rawValue;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected static function normalizeBooleanValue($value)
    {
        return $value && $value !== 'N' ? true : false;
    }

    /**
     * @param mixed $value
     * @return int|float
     */
    protected static function normalizeNumericValue($value)
    {
        return strstr($value, '.') ? (float)$value : (int)$value;
    }

    /**
     * @param mixed $value
     * @return DateTime
     * @throws Exception
     */
    protected static function normalizeDateTimeValue($value)
    {
        return new DateTime($value);
    }
}