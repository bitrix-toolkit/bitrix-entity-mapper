<?php

namespace Sheerockoff\BitrixEntityMapper\Annotation\Entity;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class InfoBlock
{
    /**
     * @var string
     * @Required
     */
    protected $type;

    /**
     * @var string
     * @Required
     */
    protected $code;

    /**
     * @var string
     */
    protected $name;

    /**
     * InfoBlock constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->type = isset($values['type']) ? $values['type'] : null;
        $this->code = isset($values['code']) ? $values['code'] : null;
        $this->name = isset($values['name']) ? $values['name'] : null;
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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}