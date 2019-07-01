<?php

namespace Sheerockoff\BitrixEntityMapper;

use CIBlock;
use CIBlockProperty;
use InvalidArgumentException;
use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;

class SchemaBuilder
{
    /**
     * @param EntityMap $entityMap
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function build(EntityMap $entityMap)
    {
        self::buildInfoBlock($entityMap->getAnnotation());
        foreach ($entityMap->getProperties() as $propertyMap) {
            $propAnnotation = $propertyMap->getAnnotation();
            if ($propAnnotation instanceof Property) {
                self::buildProperty($entityMap->getAnnotation(), $propAnnotation);
            }
        }

        return true;
    }

    /**
     * @param InfoBlock $annotation
     * @return int
     * @throws InvalidArgumentException
     */
    protected static function buildInfoBlock(InfoBlock $annotation)
    {
        $type = $annotation->getType();
        self::assert($type, 'Не указан тип инфоблока.');
        $code = $annotation->getCode();
        self::assert($code, 'Не указан код инфоблока.');
        $name = $annotation->getName();

        $fields = [
            'LID' => SITE_ID,
            'CODE' => $code,
            'IBLOCK_TYPE_ID' => $type,
            'NAME' => $name ? $name : $code,
            'GROUP_ID' => ['1' => 'X', '2' => 'W']
        ];

        $exist = self::getBitrixInfoBlock($type, $code);
        if (!empty($exist['ID'])) {
            $iBlockId = $exist['ID'];
            $cIBlock = new CIBlock();
            $isUpdated = $cIBlock->Update($iBlockId, $fields);
            self::assert($isUpdated, strip_tags($cIBlock->LAST_ERROR));
        } else {
            $cIBlock = new CIBlock();
            /** @var int|bool $iBlockId */
            $iBlockId = $cIBlock->Add($fields);
            self::assert($iBlockId, strip_tags($cIBlock->LAST_ERROR));
        }

        return $iBlockId;
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
     * @param string $type
     * @param string $code
     * @return array
     * @throws InvalidArgumentException
     */
    protected static function getBitrixInfoBlock($type, $code)
    {
        self::assert($type, 'Не указан тип инфоблока.');
        self::assert($code, 'Не указан код инфоблока.');

        $iBlock = CIBlock::GetList(null, [
            '=TYPE' => $type,
            '=CODE' => $code,
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        return $iBlock;
    }

    /**
     * @param int $iBlockId
     * @param string $code
     * @return array|null
     * @throws InvalidArgumentException
     */
    protected static function getBitrixProperty($iBlockId, $code)
    {
        self::assert($iBlockId, 'Не указан ID инфоблока.');
        self::assert($code, 'Не указан код свойства.');

        $prop = CIBlockProperty::GetList(null, [
            'IBLOCK_ID' => $iBlockId,
            'CODE' => $code,
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        return $prop;
    }

    protected static $propertyMap = [
        Property::TYPE_INTEGER => ['PROPERTY_TYPE' => 'N', 'USER_TYPE' => false],
        Property::TYPE_FLOAT => ['PROPERTY_TYPE' => 'N', 'USER_TYPE' => false],
        Property::TYPE_DATETIME => ['PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'DateTime'],
        Property::TYPE_FILE => ['PROPERTY_TYPE' => 'F', 'USER_TYPE' => false],
        Property::TYPE_BOOLEAN => ['PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'C', 'USER_TYPE' => false],
        Property::TYPE_ENTITY => ['PROPERTY_TYPE' => 'E', 'USER_TYPE' => false],
        Property::TYPE_STRING => ['PROPERTY_TYPE' => 'S', 'USER_TYPE' => false],
    ];

    /**
     * @param InfoBlock $entityAnnotation
     * @param Property $propertyAnnotation
     * @return bool
     * @throws InvalidArgumentException
     */
    protected static function buildProperty(InfoBlock $entityAnnotation, Property $propertyAnnotation)
    {
        self::assert($propertyAnnotation->getCode(), 'Не указан код свойства.');
        self::assert($propertyAnnotation->getType(), 'Не указан тип свойства.');

        $iBlock = self::getBitrixInfoBlock($entityAnnotation->getType(), $entityAnnotation->getCode());
        self::assert(
            !empty($iBlock['ID']),
            "Инфоблок с кодом {$entityAnnotation->getCode()} и типом {$entityAnnotation->getType()} не найден."
        );

        $fields = self::generateBitrixFields($iBlock['ID'], $propertyAnnotation);

        $exist = self::getBitrixProperty($iBlock['ID'], $propertyAnnotation->getCode());
        if (!empty($exist['ID'])) {
            $propId = $exist['ID'];
            self::updateProperty($propId, $propertyAnnotation, $fields);
        } else {
            $propId = self::addProperty($propertyAnnotation, $fields);
        }

        return $propId;
    }

    /**
     * @param int $iBlockId
     * @param Property $propertyAnnotation
     * @return array
     * @throws InvalidArgumentException
     */
    protected static function generateBitrixFields($iBlockId, Property $propertyAnnotation)
    {
        $fields = [
            'IBLOCK_ID' => $iBlockId,
            'CODE' => $propertyAnnotation->getCode(),
            'NAME' => $propertyAnnotation->getName() ? $propertyAnnotation->getName() : $propertyAnnotation->getCode(),
            'MULTIPLE' => $propertyAnnotation->isMultiple() ? 'Y' : 'N',
            'FILTRABLE' => 'Y'
        ];

        if (array_key_exists($propertyAnnotation->getType(), self::$propertyMap)) {
            $fields += self::$propertyMap[$propertyAnnotation->getType()];
        }

        return $fields;
    }

    /**
     * @param int $propId
     * @param Property $propertyAnnotation
     * @param array $fields
     * @return bool
     * @throws InvalidArgumentException
     */
    protected static function updateProperty($propId, Property $propertyAnnotation, array $fields)
    {
        $type = $propertyAnnotation->getType();
        if ($type === Property::TYPE_BOOLEAN) {
            $existEnum = CIBlockProperty::GetPropertyEnum($propId, null, ['XML_ID' => 'Y', 'VALUE' => 'Y'])->Fetch();
            if (!$existEnum) {
                $fields += ['VALUES' => [['XML_ID' => 'Y', 'VALUE' => 'Y', 'DEF' => 'N']]];
            }
        }

        $cIBlockProperty = new CIBlockProperty();
        $isUpdated = $cIBlockProperty->Update($propId, $fields);
        self::assert($isUpdated, strip_tags($cIBlockProperty->LAST_ERROR));

        return $isUpdated;
    }

    /**
     * @param Property $propertyAnnotation
     * @param array $fields
     * @return int
     * @throws InvalidArgumentException
     */
    protected static function addProperty(Property $propertyAnnotation, array $fields)
    {
        $type = $propertyAnnotation->getType();
        if ($type === Property::TYPE_BOOLEAN) {
            $fields += ['VALUES' => [['XML_ID' => 'Y', 'VALUE' => 'Y', 'DEF' => 'N']]];
        }

        $cIBlockProperty = new CIBlockProperty();
        $propId = $cIBlockProperty->Add($fields);
        self::assert($propId, strip_tags($cIBlockProperty->LAST_ERROR));

        return $propId;
    }
}