<?php

namespace BitrixToolkit\BitrixEntityMapper\Map;

use Doctrine\Common\Annotations\AnnotationException;
use InvalidArgumentException;
use ReflectionProperty;
use BitrixToolkit\BitrixEntityMapper\Annotation\AnnotationReader;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;

class PropertyMap
{
    /**
     * @var string
     */
    protected $code;

    /**
     * @var PropertyAnnotationInterface
     */
    protected $annotation;

    /**
     * @var ReflectionProperty
     */
    protected $reflection;

    /**
     * PropertyMap constructor.
     * @param string $code
     * @param PropertyAnnotationInterface $annotation
     * @param ReflectionProperty $reflection
     */
    public function __construct($code, PropertyAnnotationInterface $annotation, ReflectionProperty $reflection)
    {
        $this->code = $code;
        $this->annotation = $annotation;
        $this->reflection = $reflection;
    }

    /**
     * @param ReflectionProperty $propRef
     * @return PropertyMap|null
     * @throws AnnotationException
     */
    public static function fromReflectionProperty(ReflectionProperty $propRef)
    {
        $annotationReader = new AnnotationReader();
        $propAnnotations = $annotationReader->getPropertyAnnotations($propRef);
        $propAnnotations = array_filter($propAnnotations, function ($propAnnotation) {
            return $propAnnotation instanceof PropertyAnnotationInterface;
        });

        if (empty($propAnnotations)) {
            return null;
        }

        if (count($propAnnotations) > 1) {
            $annotationClasses = array_map(function ($propAnnotation) {
                return get_class($propAnnotation);
            }, $propAnnotations);

            throw new InvalidArgumentException(
                'Аннотации ' . '@' . implode(', @', $annotationClasses) .
                ' свойства ' . $propRef->getName() . ' класса ' . $propRef->getDeclaringClass()->getName() .
                ' не могут быть применены одновременно.'
            );
        }

        /** @var PropertyAnnotationInterface $propAnnotation */
        $propAnnotation = reset($propAnnotations);
        return new PropertyMap($propRef->getName(), $propAnnotation, $propRef);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return PropertyAnnotationInterface
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }

    /**
     * @return ReflectionProperty
     */
    public function getReflection()
    {
        return $this->reflection;
    }
}