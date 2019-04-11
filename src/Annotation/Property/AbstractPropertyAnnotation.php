<?php

namespace Sheerockoff\BitrixEntityMapper\Annotation\Property;

abstract class AbstractPropertyAnnotation implements PropertyAnnotationInterface
{
    protected $code;
    protected $type;
    protected $primaryKey = false;
    protected $name;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }
}