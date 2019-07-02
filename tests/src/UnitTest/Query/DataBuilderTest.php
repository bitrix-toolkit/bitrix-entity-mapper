<?php

namespace Sheerockoff\BitrixEntityMapper\Test\UnitTest\Query;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Query\DataBuilder;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

class DataBuilderTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testCanAssert()
    {
        $dataBuilderRef = new ReflectionClass(DataBuilder::class);
        $assertRef = $dataBuilderRef->getMethod('assert');
        $assertRef->setAccessible(true);
        $this->expectException(InvalidArgumentException::class);
        $assertRef->invoke(null, false, 'Test assertion.');
    }
}