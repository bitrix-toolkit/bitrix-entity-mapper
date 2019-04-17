<?php

use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\Map\PropertyMap;

final class EntityMapTest extends BitrixEntityMapperTestCase
{
    /**
     * @throws ReflectionException
     * @throws AnnotationException
     */
    public function testCanBuildEntityMap()
    {
        $entityMap = EntityMap::fromClass(Book::class);
        $this->assertInstanceOf(EntityMap::class, $entityMap);
        $this->assertContainsOnlyInstancesOf(PropertyMap::class, $entityMap->getProperties());
    }
}