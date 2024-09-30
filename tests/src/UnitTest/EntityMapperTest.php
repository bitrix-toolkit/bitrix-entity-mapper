<?php

namespace BitrixToolkit\BitrixEntityMapper\Test\UnitTest;

use Doctrine\Common\Annotations\AnnotationException;
use InvalidArgumentException;
use ReflectionException;
use BitrixToolkit\BitrixEntityMapper\EntityMapper;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;

final class EntityMapperTest extends TestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanAssert()
    {
        $this->expectException(InvalidArgumentException::class);
        EntityMapper::save(null);
    }
}