<?php

namespace Sheerockoff\BitrixEntityMapper\Annotation\Property;

use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property extends AbstractPropertyAnnotation implements PropertyAnnotationInterface
{
    /**
     * @var string
     * @Required
     */
    protected $code;

    /**
     * @var string
     * @Required
     * @Enum({"string", "boolean", "integer", "float", "datetime", "file"})
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $primaryKey;

    public function __construct(array $values)
    {
        $this->code = isset($values['code']) ? $values['code'] : null;
        $this->type = isset($values['type']) ? $values['type'] : null;
        $this->name = isset($values['name']) ? $values['name'] : null;
        $this->primaryKey = isset($values['primaryKey']) ? (bool)$values['primaryKey'] : false;
    }
}