<?php

namespace Sheerockoff\BitrixEntityMapper;

use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use InvalidArgumentException;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\Map\PropertyMap;
use Sheerockoff\BitrixEntityMapper\Query\RawResult;
use Sheerockoff\BitrixEntityMapper\Query\Select;

class EntityMapper
{
    /**
     * @param object $object
     * @return bool|int
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function save($object)
    {
        self::assert(is_object($object), 'Аргумент $object не является объектом.');
        $class = get_class($object);
        $entityMap = EntityMap::fromClass($class);

        $fields = array_filter($entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Field;
        });

        $properties = array_filter($entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation() instanceof Property;
        });

        $data = self::entityToArray($entityMap, $object);
        $exist = self::getExistObjectRawResult($entityMap, $object);

        if ($exist && $exist->getId()) {
            $changedData = array_udiff_assoc($data, $exist->getData(), function ($a, $b) {
                if ($a instanceof DateTime && $b instanceof DateTime) {
                    return $a->getTimestamp() - $b->getTimestamp();
                }

                return $a !== $b;
            });

            if (!$changedData) {
                return $exist->getId();
            }

            $changedFields = array_filter($fields, function (PropertyMap $field) use ($changedData) {
                return in_array($field->getReflection()->getName(), array_keys($changedData));
            });

            $bitrixFields = [];
            if ($changedFields) {
                foreach ($changedFields as $changedField) {
                    $bitrixFields += self::getBitrixFieldEntry($changedField, $data);
                }

                $cIBlockElement = new CIBlockElement();
                $isUpdated = $cIBlockElement->Update($exist->getId(), $bitrixFields);
                self::assert($isUpdated, strip_tags($cIBlockElement->LAST_ERROR));
            }

            $changedProperties = array_filter($properties, function (PropertyMap $property) use ($changedData) {
                return in_array($property->getReflection()->getName(), array_keys($changedData));
            });

            $bitrixProperties = [];
            if ($changedProperties) {
                foreach ($changedProperties as $changedProperty) {
                    $bitrixProperties += self::getBitrixPropertyEntry($changedProperty, $data, $exist->getInfoBlockId());
                }

                CIBlockElement::SetPropertyValuesEx($exist->getId(), $exist->getInfoBlockId(), $bitrixProperties);
            }

            return $exist->getId();
        } else {
            $infoBlockType = $entityMap->getAnnotation()->getType();
            $infoBlockCode = $entityMap->getAnnotation()->getCode();
            $infoBlock = self::getBitrixInfoBlock($infoBlockType, $infoBlockCode);
            self::assert(!empty($infoBlock['ID']), "Не найден инфоблок с кодом $infoBlockCode и типом $infoBlockType.");

            $bitrixFields = ['IBLOCK_ID' => $infoBlock['ID']];
            foreach ($fields as $field) {
                $bitrixFields += self::getBitrixFieldEntry($field, $data);
            }

            $bitrixProperties = [];
            foreach ($properties as $property) {
                $bitrixProperties += self::getBitrixPropertyEntry($property, $data, $infoBlock['ID']);
            }

            $addFields = $bitrixFields;
            if ($bitrixProperties) {
                $addFields['PROPERTY_VALUES'] = $bitrixProperties;
            }

            $cIBlockElement = new CIBlockElement();
            $elementId = $cIBlockElement->Add($addFields);
            self::assert($elementId, strip_tags($cIBlockElement->LAST_ERROR));

            return $elementId;
        }
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
     * @param EntityMap $entityMap
     * @param object $object
     * @return array
     */
    protected static function entityToArray(EntityMap $entityMap, $object)
    {
        $data = [];
        foreach ($entityMap->getProperties() as $propertyMap) {
            if (!$propertyMap->getReflection()->isPublic()) {
                $propertyMap->getReflection()->setAccessible(true);
                $value = $propertyMap->getReflection()->getValue($object);
                $propertyMap->getReflection()->setAccessible(false);
            } else {
                $value = $propertyMap->getReflection()->getValue($object);
            }

            $data[$propertyMap->getReflection()->getName()] = $value;
        }

        return $data;
    }

    /**
     * @param EntityMap $entityMap
     * @param object $object
     * @return RawResult|null
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected static function getExistObjectRawResult(EntityMap $entityMap, $object)
    {
        /** @var PropertyMap[] $primaryKeys */
        $primaryKeys = array_filter($entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation()->isPrimaryKey();
        });

        $data = self::entityToArray($entityMap, $object);

        $exist = null;
        if ($primaryKeys) {
            $select = Select::from($entityMap->getClass());
            foreach ($primaryKeys as $primaryKey) {
                $key = $primaryKey->getReflection()->getName();
                self::assert(array_key_exists($key, $data), "Ключ $key не найден в массиве данных полученных из объекта.");
                $select->where($key, $data[$key]);
            }

            /** @var RawResult $exist */
            $exist = $select->rawIterator()->current();
        }

        return $exist;
    }

    /**
     * @param PropertyMap $propertyMap
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    protected static function getBitrixFieldEntry(PropertyMap $propertyMap, array $data)
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
     * @param PropertyMap $propertyMap
     * @param array $data
     * @param int $infoBlockId
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected static function getBitrixPropertyEntry(PropertyMap $propertyMap, array $data, $infoBlockId)
    {
        self::assert(
            $propertyMap->getAnnotation() instanceof Property,
            'Аннотация свойства должна быть экземпляром ' . Property::class . '.'
        );

        $key = $propertyMap->getAnnotation()->getCode();

        $valueKey = $propertyMap->getReflection()->getName();
        self::assert(array_key_exists($valueKey, $data), "Ключ $valueKey не найден в массиве.");
        $value = $data[$valueKey];

        if ($propertyMap->getAnnotation()->getType() === Property::TYPE_BOOLEAN) {
            if ($value) {
                $yesEnum = CIBlockProperty::GetPropertyEnum(
                    $propertyMap->getAnnotation()->getCode(),
                    null,
                    [
                        'IBLOCK_ID' => $infoBlockId,
                        'XML_ID' => 'Y',
                        'VALUE' => 'Y'
                    ]
                )->Fetch();

                self::assert(
                    !empty($yesEnum['ID']),
                    'Не найден ID варианта ответа Y для булевого значения свойства '
                    . $propertyMap->getAnnotation()->getCode() . '.'
                );

                $value = $yesEnum['ID'];
            } else {
                $value = false;
            }
        } elseif ($propertyMap->getAnnotation()->getType() === Property::TYPE_DATETIME) {
            if ($value) {
                if ($value instanceof DateTime) {
                    $value = BitrixDateTime::createFromTimestamp($value->getTimestamp());
                } elseif ($value instanceof BitrixDateTime) {
                    // pass
                } elseif (preg_match('/^-?\d+$/us', (string)$value)) {
                    $value = BitrixDateTime::createFromTimestamp($value);
                } else {
                    $value = BitrixDateTime::createFromPhp(new DateTime($value));
                }
            } else {
                $value = false;
            }
        }

        return [$key => $value];
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
            'CHECK_PERMISSIIONS' => 'N'
        ])->Fetch();
    }

    /**
     * @param string $class
     * @return Select
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public static function select($class)
    {
        return Select::from($class);
    }
}