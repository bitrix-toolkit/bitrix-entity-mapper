<?php

namespace Sheerockoff\BitrixEntityMapper\Test\UnitTest;

use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\SchemaBuilder;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

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
        $schemaBuilder = new SchemaBuilder($entityMap);

        $this->expectException(InvalidArgumentException::class);
        $schemaBuilder->build();
    }
}