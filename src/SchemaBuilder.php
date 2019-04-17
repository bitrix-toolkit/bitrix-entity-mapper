<?php

namespace Sheerockoff\BitrixEntityMapper;

use CIBlock;
use CIBlockProperty;
use InvalidArgumentException;
use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;

class SchemaBuilder
{
    /**
     * @var EntityMap
     */
    protected $entityMap;

    /**
     * SchemaBuilder constructor.
     * @param EntityMap $entityMap
     */
    public function __construct(EntityMap $entityMap)
    {
        $this->entityMap = $entityMap;
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    public function build()
    {
        self::buildInfoBlock($this->entityMap->getAnnotation());
        foreach ($this->entityMap->getProperties() as $propertyMap) {
            $propAnnotation = $propertyMap->getAnnotation();
            if ($propAnnotation instanceof Property) {
                self::buildProperty($this->entityMap->getAnnotation(), $propAnnotation);
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
     */
    protected static function getBitrixInfoBlock($type, $code)
    {
        return CIBlock::GetList(null, [
            '=TYPE' => $type,
            '=CODE' => $code,
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();
    }

    /**
     * @param InfoBlock $entityAnnotation
     * @param Property $propertyAnnotation
     * @return bool
     * @throws InvalidArgumentException
     */
    protected static function buildProperty(InfoBlock $entityAnnotation, Property $propertyAnnotation)
    {
        $iBlockType = $entityAnnotation->getType();
        self::assert($iBlockType, 'Не указан тип инфоблока.');
        $iBlockCode = $entityAnnotation->getCode();
        self::assert($iBlockCode, 'Не указан код инфоблока.');

        $iBlock = self::getBitrixInfoBlock($iBlockType, $iBlockCode);
        self::assert(!empty($iBlock['ID']), "Инфоблок с кодом $iBlockCode и типом $iBlockType не найден.");

        $code = $propertyAnnotation->getCode();
        self::assert($code, 'Не указан код свойства.');
        $type = $propertyAnnotation->getType();
        self::assert($type, 'Не указан тип свойства.');
        $name = $propertyAnnotation->getName();

        $fields = [
            'IBLOCK_ID' => $iBlock['ID'],
            'CODE' => $code,
            'NAME' => $name ? $name : $code,
            'FILTRABLE' => 'Y'
        ];

        if ($type === PropertyAnnotationInterface::TYPE_INTEGER || $type === PropertyAnnotationInterface::TYPE_FLOAT) {
            $fields += [
                'PROPERTY_TYPE' => 'N'
            ];
        } elseif ($type === PropertyAnnotationInterface::TYPE_DATETIME) {
            $fields += [
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'DateTime'
            ];
        } elseif ($type === PropertyAnnotationInterface::TYPE_FILE) {
            $fields += [
                'PROPERTY_TYPE' => 'F'
            ];
        } elseif ($type === PropertyAnnotationInterface::TYPE_BOOLEAN) {
            $fields += [
                'PROPERTY_TYPE' => 'L',
                'LIST_TYPE' => 'C'
            ];
        } else {
            $fields += [
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => false
            ];
        }

        $exist = CIBlockProperty::GetList(null, [
            'IBLOCK_ID' => $iBlock['ID'],
            'CODE' => $code,
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        if (!empty($exist['ID'])) {
            $propId = $exist['ID'];
            if ($type === PropertyAnnotationInterface::TYPE_BOOLEAN) {
                $existEnum = CIBlockProperty::GetPropertyEnum($propId, null, ['XML_ID' => 'Y', 'VALUE' => 'Y'])->Fetch();
                if (!$existEnum) {
                    $fields += ['VALUES' => [['XML_ID' => 'Y', 'VALUE' => 'Y', 'DEF' => 'N']]];
                }
            }

            $cIBlockProperty = new CIBlockProperty();
            $isUpdated = $cIBlockProperty->Update($propId, $fields);
            self::assert($isUpdated, strip_tags($cIBlockProperty->LAST_ERROR));
        } else {
            if ($type === PropertyAnnotationInterface::TYPE_BOOLEAN) {
                $fields += ['VALUES' => [['XML_ID' => 'Y', 'VALUE' => 'Y', 'DEF' => 'N']]];
            }
            $cIBlockProperty = new CIBlockProperty();
            $propId = $cIBlockProperty->Add($fields);
            self::assert($propId, strip_tags($cIBlockProperty->LAST_ERROR));
        }

        return $propId;
    }
}