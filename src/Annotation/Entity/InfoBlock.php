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
    public $type;

    /**
     * @var string
     * @Required
     */
    public $code;

    /**
     * @var string
     */
    public $name;
}