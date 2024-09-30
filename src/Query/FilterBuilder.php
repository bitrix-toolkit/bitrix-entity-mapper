<?php

namespace BitrixToolkit\BitrixEntityMapper\Query;

use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CIBlock;
use DateTime;
use Exception;
use InvalidArgumentException;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Field;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;

class FilterBuilder
{
    protected $entityMap;
    protected $where = [];
    protected $whereRaw = [];

    public function __construct(EntityMap $entityMap, array $where, array $whereRaw)
    {
        $this->entityMap = $entityMap;
        $this->where = $where;
        $this->whereRaw = $whereRaw;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFilter()
    {
        $filter = $this->getInfoBlockFilter();

        foreach ($this->where as $entry) {
            list($property, $operator, $value) = $entry;
            $filter += $this->getFilterRow($property, $operator, $value);
        }

        $filter = array_merge($filter, $this->whereRaw);

        return $filter;
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
     * @return array|null
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function getFilterRow($property, $operator, $value)
    {
        $propertyMap = $this->entityMap->getProperty($property);
        $propertyAnnotation = $propertyMap->getAnnotation();
        $type = $propertyAnnotation->getType();
        $code = $propertyAnnotation->getCode();

        if ($propertyAnnotation instanceof Field) {
            return $this->getFieldFilterRow($type, $code, $operator, $value);
        } else {
            return $this->getPropertyFilterRow($type, $code, $operator, $value);
        }
    }

    /**
     * @param string $type
     * @param string $code
     * @param string $operator
     * @param mixed $value
     * @return array
     */
    protected static function getFieldFilterRow($type, $code, $operator, $value)
    {
        $k = $operator . $code;
        if ($type === Field::TYPE_BOOLEAN) {
            $v = $value && $value !== 'N' ? 'Y' : 'N';
        } else {
            $v = $value !== '' && $value !== null ? $value : false;
        }

        return [$k => $v];
    }

    /**
     * @param string $type
     * @param string $code
     * @param string $operator
     * @param mixed $value
     * @return array
     * @throws Exception
     */
    protected static function getPropertyFilterRow($type, $code, $operator, $value)
    {
        $k = self::getPropertyFilterKey($type, $code, $operator);
        $v = self::getPropertyFilterValue($type, $value);
        return [$k => $v];
    }

    /**
     * @param string $type
     * @param string $code
     * @param string $operator
     * @return string
     */
    protected static function getPropertyFilterKey($type, $code, $operator)
    {
        if ($type === Property::TYPE_BOOLEAN) {
            return "{$operator}PROPERTY_{$code}_VALUE";
        } else {
            return "{$operator}PROPERTY_{$code}";
        }
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    protected static function getPropertyFilterValue($type, $value)
    {
        if ($type === Property::TYPE_BOOLEAN) {
            return $value && $value !== 'N' ? 'Y' : false;
        }

        if ($type === Property::TYPE_DATETIME) {
            $dateTime = self::toDateTime($value);
            return $dateTime instanceof DateTime ? $dateTime->format('Y-m-d H:i:s') : false;
        }

        return ($value === '' || $value === null) ? false : $value;
    }

    /**
     * @param mixed $value
     * @return DateTime|false|null
     * @throws Exception
     */
    protected static function toDateTime($value)
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        if ($value instanceof BitrixDateTime) {
            return DateTime::createFromFormat('Y-m-d H:i:s', $value->format('Y-m-d H:i:s'));
        }

        return new DateTime($value);
    }
}