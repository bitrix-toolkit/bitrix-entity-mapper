<?php

namespace BitrixToolkit\BitrixEntityMapper\Test\UnitTest;

use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use BitrixToolkit\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use BitrixToolkit\BitrixEntityMapper\SchemaBuilder;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;

final class SchemaBuilderTest extends TestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanAssert()
    {
        $entityMap = EntityMap::fromClass(Book::class);
        $annotationClassReflection = new ReflectionClass(InfoBlock::class);
        $annotationTypePropReflection = $annotationClassReflection->getProperty('type');
        $annotationTypePropReflection->setAccessible(true);
        $annotationTypePropReflection->setValue($entityMap->getAnnotation(), '');
        $annotationTypePropReflection->setAccessible(false);

        $this->expectException(InvalidArgumentException::class);
        SchemaBuilder::build($entityMap);
    }
}