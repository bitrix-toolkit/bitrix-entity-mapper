<?php

namespace Sheerockoff\BitrixEntityMapper\Map;

use Doctrine\Common\Annotations\AnnotationException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Annotation\AnnotationReader;
use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;

class EntityMap
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var InfoBlock
     */
    protected $annotation;

    /**
     * @var PropertyMap[]
     */
    protected $properties;

    /**
     * EntityMap constructor.
     * @param string $class
     * @param InfoBlock $annotation
     * @param PropertyMap[] $properties
     */
    public function __construct($class, InfoBlock $annotation, array $properties)
    {
        $this->class = $class;
        $this->annotation = $annotation;
        $this->properties = $properties;
    }

    /**
     * @param string|object $class
     * @return EntityMap
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public static function fromClass($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        $annotationReader = new AnnotationReader();
        $classRef = new ReflectionClass($class);

        /** @var InfoBlock $classAnnotation */
        $classAnnotation = $annotationReader->getClassAnnotation($classRef, InfoBlock::class);
        if (!$classAnnotation) {
            throw new InvalidArgumentException('Нет аннотации @' . InfoBlock::class . ' для класса ' . $classRef->getName() . '.');
        }

        $propertyMaps = [];
        foreach ($classRef->getProperties() as $propRef) {
            $propAnnotations = $annotationReader->getPropertyAnnotations($propRef);
            $propAnnotations = array_filter($propAnnotations, function ($propAnnotation) {
                return $propAnnotation instanceof PropertyAnnotationInterface;
            });

            if (!$propAnnotations) {
                continue;
            }

            if (count($propAnnotations) > 1) {
                $annotationClasses = array_map(function ($propAnnotation) {
                    return get_class($propAnnotation);
                }, $propAnnotations);

                throw new InvalidArgumentException(
                    'Аннотации ' . '@' . implode(', @', $annotationClasses) .
                    ' свойства ' . $propRef->getName() . ' класса ' . $classRef->getName() .
                    ' не могут быть применены одновременно.'
                );
            }

            /** @var PropertyAnnotationInterface $propAnnotation */
            $propAnnotation = reset($propAnnotations);
            $propertyMaps[] = new PropertyMap($propRef->getName(), $propAnnotation);
        }

        $entityMap = new self($classRef->getName(), $classAnnotation, $propertyMaps);
        return $entityMap;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return InfoBlock
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }

    /**
     * @return PropertyMap[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $code
     * @return PropertyMap
     */
    public function getProperty($code)
    {
        return reset(array_filter($this->properties, function (PropertyMap $property) use ($code) {
            return $property->getCode() === $code;
        }));
    }
}