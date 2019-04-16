<?php

namespace Sheerockoff\BitrixEntityMapper\Map;

use ReflectionProperty;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;

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