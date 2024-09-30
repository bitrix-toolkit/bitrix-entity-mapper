<?php

namespace BitrixToolkit\BitrixEntityMapper\Test\UnitTest\Map;

use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use Entity\WithConflictPropertyAnnotations;
use Entity\WithoutInfoBlockAnnotation;
use InvalidArgumentException;
use ReflectionException;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;

final class EntityMapTest extends TestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanAssertOnMissedClassAnnotation()
    {
        $this->expectException(InvalidArgumentException::class);
        EntityMap::fromClass(WithoutInfoBlockAnnotation::class);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanAssertOnConflictedAnnotations()
    {
        $this->expectException(InvalidArgumentException::class);
        EntityMap::fromClass(WithConflictPropertyAnnotations::class);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanAssertOnTryingGetNotMappedProperty()
    {
        $entityMap = EntityMap::fromClass(Book::class);
        $this->expectException(InvalidArgumentException::class);
        $entityMap->getProperty('notMappedProperty');
    }
}