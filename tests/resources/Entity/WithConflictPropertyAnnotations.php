<?php

namespace Entity;

use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;

/**
 * Class WithConflictPropertyAnnotations
 * @package Entity
 * @InfoBlock(type="test_entity", code="conflicted", name="Конфликтные")
 */
class WithConflictPropertyAnnotations
{
    /**
     * @var string
     * @Property(code="var", type="string")
     * @Field(code="NAME")
     */
    public $var;
}