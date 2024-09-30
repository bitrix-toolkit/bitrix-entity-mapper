<?php

namespace Entity;

use BitrixToolkit\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Field;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;

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