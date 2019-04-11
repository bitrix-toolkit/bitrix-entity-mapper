<?php

namespace Sheerockoff\BitrixEntityMapper\Map;

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
     * PropertyMap constructor.
     * @param string $code
     * @param PropertyAnnotationInterface $annotation
     */
    public function __construct($code, PropertyAnnotationInterface $annotation)
    {
        $this->code = $code;
        $this->annotation = $annotation;
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
}