<?php

namespace BitrixToolkit\BitrixEntityMapper\Test\UnitTest\Query;

use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use BitrixToolkit\BitrixEntityMapper\Query\Select;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;

final class SelectTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testCanAssert()
    {
        $selectRef = new ReflectionClass(Select::class);
        $assertRef = $selectRef->getMethod('assert');
        $assertRef->setAccessible(true);
        $this->expectException(InvalidArgumentException::class);
        $assertRef->invoke(null, false, 'Test assertion.');
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanAssertOnSchemaMissing()
    {
        self::deleteInfoBlocks();
        $this->expectException(InvalidArgumentException::class);
        Select::from(Book::class)->fetch();
    }
}