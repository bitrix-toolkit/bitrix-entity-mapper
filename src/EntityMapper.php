<?php

namespace BitrixToolkit\BitrixEntityMapper;

use CIBlockElement;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use InvalidArgumentException;
use ReflectionException;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Field;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use BitrixToolkit\BitrixEntityMapper\Map\PropertyMap;
use BitrixToolkit\BitrixEntityMapper\Query\DataBuilder;
use BitrixToolkit\BitrixEntityMapper\Query\RawResult;
use BitrixToolkit\BitrixEntityMapper\Query\Select;

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

        $data = self::entityToArray($entityMap, $object);

        // Сохраняем вложенные сущности
        $entityData = self::saveChildEntities($entityMap, $data);
        $data = array_replace($data, $entityData);

        $exist = self::getExistObjectRawResult($entityMap, $object);

        if ($exist && $exist->getId()) {
            return self::update($exist, $entityMap, $data);
        } else {
            return self::add($entityMap, $data);
        }
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

    /**
     * @param EntityMap $entityMap
     * @param array $data
     * @return int
     * @throws Exception
     */
    protected static function add(EntityMap $entityMap, array $data)
    {
        $bitrixFields = DataBuilder::getBitrixFields($entityMap, $data);
        $bitrixProperties = DataBuilder::getBitrixProperties($entityMap, $data, $bitrixFields['IBLOCK_ID']);

        $addFields = $bitrixFields;
        if (!empty($bitrixProperties)) {
            $addFields['PROPERTY_VALUES'] = $bitrixProperties;
        }

        $cIBlockElement = new CIBlockElement();
        $elementId = $cIBlockElement->Add($addFields);
        self::assert($elementId, strip_tags($cIBlockElement->LAST_ERROR));

        return $elementId;
    }

    /**
     * @param RawResult $exist
     * @param EntityMap $entityMap
     * @param array $data
     * @return int
     * @throws Exception
     */
    protected static function update(RawResult $exist, EntityMap $entityMap, array $data)
    {
        $changedData = self::getChangedData($exist->getData(), $data);

        if (empty($changedData)) {
            return $exist->getId();
        }

        self::updateBitrixFields($exist, $entityMap, $changedData);
        self::updateBitrixProperties($exist, $entityMap, $changedData);

        return $exist->getId();
    }

    /**
     * @param array $exist
     * @param array $data
     * @return array
     */
    protected static function getChangedData(array $exist, array $data)
    {
        return array_udiff_assoc($data, $exist, function ($new, $old) {
            $normalize = function ($value) {
                if ($value instanceof DateTime) {
                    return $value->getTimestamp();
                }

                return $value;
            };

            $new = $new === null || $new === false || $new === [] ? false : array_map($normalize, (array)$new);
            $old = $old === null || $old === false || $old === [] ? false : array_map($normalize, (array)$old);

            return $new !== $old;
        });
    }

    /**
     * @param RawResult $exist
     * @param EntityMap $entityMap
     * @param array $changedData
     * @throws Exception
     */
    protected static function updateBitrixFields(RawResult $exist, EntityMap $entityMap, array $changedData)
    {
        $changedFields = array_filter($entityMap->getProperties(), function (PropertyMap $field) use ($changedData) {
            return (
                $field->getAnnotation() instanceof Field &&
                in_array($field->getReflection()->getName(), array_keys($changedData))
            );
        });

        if (empty($changedFields)) {
            return;
        }

        $bitrixFields = [];
        foreach ($changedFields as $changedField) {
            $bitrixFields += DataBuilder::getBitrixFieldEntry($changedField, $changedData);
        }

        $cIBlockElement = new CIBlockElement();
        $isUpdated = $cIBlockElement->Update($exist->getId(), $bitrixFields);
        self::assert($isUpdated, strip_tags($cIBlockElement->LAST_ERROR));
    }

    /**
     * @param RawResult $exist
     * @param EntityMap $entityMap
     * @param array $changedData
     * @throws Exception
     */
    protected static function updateBitrixProperties(RawResult $exist, EntityMap $entityMap, array $changedData)
    {
        $changedProperties = array_filter($entityMap->getProperties(), function (PropertyMap $property) use ($changedData) {
            return (
                $property->getAnnotation() instanceof Property &&
                in_array($property->getReflection()->getName(), array_keys($changedData))
            );
        });

        if (empty($changedProperties)) {
            return;
        }

        $bitrixProperties = [];
        foreach ($changedProperties as $changedProperty) {
            $bitrixProperties += DataBuilder::getBitrixPropertyEntry($changedProperty, $changedData, $exist->getInfoBlockId());
        }

        CIBlockElement::SetPropertyValuesEx($exist->getId(), $exist->getInfoBlockId(), $bitrixProperties);
    }

    /**
     * @param EntityMap $entityMap
     * @param array $data
     * @return array
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    protected static function saveChildEntities(EntityMap $entityMap, array $data)
    {
        /** @var PropertyMap[] $entityProperties */
        $entityProperties = array_filter($entityMap->getProperties(), function (PropertyMap $propertyMap) {
            return $propertyMap->getAnnotation()->getType() === Property::TYPE_ENTITY;
        });

        foreach ($entityProperties as $entityProperty) {
            self::checkChildEntity($entityProperty, $data);
        }

        $entityData = [];
        foreach ($entityProperties as $entityProperty) {
            $entityPropertyData = self::saveChildEntity($entityProperty, $data);
            $entityData += $entityPropertyData;
        }

        return $entityData;
    }

    /**
     * @param PropertyMap $entityProperty
     * @param array $data
     * @return array
     * @throws AnnotationException
     * @throws ReflectionException
     */
    protected static function saveChildEntity(PropertyMap $entityProperty, array $data)
    {
        $entityData = [];
        $key = $entityProperty->getCode();

        $rawValue = array_key_exists($key, $data) ? $data[$key] : null;
        if (empty($rawValue)) {
            $entityData[$key] = false;
            return $entityData;
        }

        if ($entityProperty->getAnnotation()->isMultiple()) {
            foreach ($rawValue as $object) {
                $objectId = self::save($object);
                $entityData[$key][] = $objectId;
            }
        } else {
            $objectId = self::save($rawValue);
            $entityData[$key] = $objectId;
        }

        return $entityData;
    }

    /**
     * @param PropertyMap $entityProperty
     * @param array $data
     * @throws InvalidArgumentException
     */
    protected static function checkChildEntity(PropertyMap $entityProperty, array $data)
    {
        $key = $entityProperty->getCode();
        self::assert(array_key_exists($key, $data), "Ключ $key не найден в массиве данных полученных из объекта.");
        $value = $data[$key];

        if ($entityProperty->getAnnotation()->isMultiple()) {
            $objects = $value ? $value : [];
            self::assert(is_array($objects), 'Множественное значение должно быть массивом.');
        } else {
            $objects = $value ? [$value] : [];
        }

        $needClass = $entityProperty->getAnnotation()->getEntity();
        foreach ($objects as $object) {
            self::assert(is_object($object), 'Значение типа ' . Property::TYPE_ENTITY . ' должно быть объектом.');
            self::assert($object instanceof $needClass, "Объект должен быть экземпляром класса $needClass.");
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
        if (!empty($primaryKeys)) {
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
}