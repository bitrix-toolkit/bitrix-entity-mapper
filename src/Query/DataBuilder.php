<?php

namespace BitrixToolkit\BitrixEntityMapper\Query;

use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CIBlock;
use CIBlockProperty;
use DateTime;
use Exception;
use InvalidArgumentException;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Field;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use BitrixToolkit\BitrixEntityMapper\Map\PropertyMap;

class DataBuilder
{
    /**
     * @param EntityMap $entityMap
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    public static function getBitrixFields(EntityMap $entityMap, array $data)
    {
        $fields = array_filter($entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Field;
        });

        $infoBlockType = $entityMap->getAnnotation()->getType();
        $infoBlockCode = $entityMap->getAnnotation()->getCode();
        $infoBlock = self::getBitrixInfoBlock($infoBlockType, $infoBlockCode);
        self::assert(!empty($infoBlock['ID']), "Не найден инфоблок с кодом $infoBlockCode и типом $infoBlockType.");

        $bitrixFields = ['IBLOCK_ID' => $infoBlock['ID']];
        foreach ($fields as $field) {
            $bitrixFields += self::getBitrixFieldEntry($field, $data);
        }

        return $bitrixFields;
    }

    /**
     * @param string $type
     * @param string $code
     * @return array
     */
    protected static function getBitrixInfoBlock($type, $code)
    {
        return CIBlock::GetList(null, [
            'TYPE' => $type,
            'CODE' => $code,
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();
    }

    /**
     * @param mixed $term
     * @param string $msg
     * @throws InvalidArgumentException
     */
    protected static function assert($term, $msg)
    {
        if (!$term) {
            throw new InvalidArgumentException($msg);
        }
    }

    /**
     * @param PropertyMap $propertyMap
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    public static function getBitrixFieldEntry(PropertyMap $propertyMap, array $data)
    {
        self::assert(
            $propertyMap->getAnnotation() instanceof Field,
            'Аннотация свойства должна быть экземпляром ' . Field::class . '.'
        );

        $key = $propertyMap->getAnnotation()->getCode();

        $valueKey = $propertyMap->getReflection()->getName();
        self::assert(array_key_exists($valueKey, $data), "Ключ $valueKey не найден в массиве.");
        $value = $data[$valueKey];

        if ($propertyMap->getAnnotation()->getType() === PropertyAnnotationInterface::TYPE_BOOLEAN) {
            $value = $value ? 'Y' : 'N';
        }

        return [$key => $value];
    }

    /**
     * @param EntityMap $entityMap
     * @param array $data
     * @param int $infoBlockId
     * @return array
     * @throws Exception
     */
    public static function getBitrixProperties(EntityMap $entityMap, array $data, $infoBlockId)
    {
        $properties = array_filter($entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Property;
        });

        $bitrixProperties = [];
        foreach ($properties as $property) {
            $bitrixProperties += self::getBitrixPropertyEntry($property, $data, $infoBlockId);
        }

        return $bitrixProperties;
    }

    /**
     * @param PropertyMap $propertyMap
     * @param array $data
     * @param int $infoBlockId
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function getBitrixPropertyEntry(PropertyMap $propertyMap, array $data, $infoBlockId)
    {
        self::assert(
            $propertyMap->getAnnotation() instanceof Property,
            'Аннотация свойства должна быть экземпляром ' . Property::class . '.'
        );

        $key = $propertyMap->getAnnotation()->getCode();

        $valueKey = $propertyMap->getReflection()->getName();
        self::assert(array_key_exists($valueKey, $data), "Ключ $valueKey не найден в массиве.");
        $value = $data[$valueKey];

        if ($propertyMap->getAnnotation()->isMultiple()) {
            $value = array_map(function ($value) use ($propertyMap, $infoBlockId) {
                return self::normalizeValueForBitrix($propertyMap, $value, $infoBlockId);
            }, (array)$value);
        } else {
            $value = self::normalizeValueForBitrix($propertyMap, $value, $infoBlockId);
        }

        return [$key => $value];
    }

    /**
     * @param PropertyMap $propertyMap
     * @param mixed $value
     * @param int $infoBlockId
     * @return mixed
     * @throws Exception
     */
    protected static function normalizeValueForBitrix(PropertyMap $propertyMap, $value, $infoBlockId)
    {
        if ($propertyMap->getAnnotation()->getType() === Property::TYPE_BOOLEAN) {
            return self::normalizeBooleanForBitrix($propertyMap->getAnnotation()->getCode(), $value, $infoBlockId);
        } elseif ($propertyMap->getAnnotation()->getType() === Property::TYPE_DATETIME) {
            return self::normalizeDateTimeForBitrix($value);
        } else {
            return $value;
        }
    }

    /**
     * @param string $code
     * @param mixed $value
     * @param int $infoBlockId
     * @return bool
     * @throws InvalidArgumentException
     */
    protected static function normalizeBooleanForBitrix($code, $value, $infoBlockId)
    {
        if (!$value) {
            return false;
        }

        $yesEnum = CIBlockProperty::GetPropertyEnum($code, null, [
            'IBLOCK_ID' => $infoBlockId,
            'XML_ID' => 'Y',
            'VALUE' => 'Y'
        ])->Fetch();

        self::assert(
            !empty($yesEnum['ID']),
            'Не найден ID варианта ответа Y для булевого значения свойства ' . $code . '.'
        );

        return $yesEnum['ID'];
    }

    /**
     * @param mixed $value
     * @return BitrixDateTime|bool
     * @throws Exception
     */
    protected static function normalizeDateTimeForBitrix($value)
    {
        if (!$value) {
            return false;
        }

        if ($value instanceof BitrixDateTime) {
            return $value;
        }

        if ($value instanceof DateTime) {
            $dateTime = BitrixDateTime::createFromTimestamp($value->getTimestamp());
        } elseif (preg_match('/^-?\d+$/us', (string)$value)) {
            $dateTime = BitrixDateTime::createFromTimestamp($value);
        } else {
            $dateTime = BitrixDateTime::createFromPhp(new DateTime($value));
        }

        return $dateTime;
    }
}