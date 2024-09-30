<?php

namespace BitrixToolkit\BitrixEntityMapper\Annotation\Property;

abstract class AbstractPropertyAnnotation implements PropertyAnnotationInterface
{
    protected $code;
    protected $type;
    protected $multiple;
    protected $primaryKey = false;
    protected $entity;
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
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
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

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }
}