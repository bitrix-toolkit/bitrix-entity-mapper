<?php

namespace Entity;

use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;

/**
 * @InfoBlock(type="test_entity", code="authors", name="Авторы")
 */
class Author
{
    /**
     * @var int
     * @Field(code="ID", primaryKey=true)
     */
    private $id;

    /**
     * @var string
     * @Field(code="NAME")
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}