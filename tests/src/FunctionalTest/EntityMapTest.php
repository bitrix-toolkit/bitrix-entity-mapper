<?php

namespace BitrixToolkit\BitrixEntityMapper\Test\FunctionalTest;

use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use BitrixToolkit\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Field;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use BitrixToolkit\BitrixEntityMapper\Map\PropertyMap;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;

final class EntityMapTest extends TestCase
{
    /**
     * @return EntityMap
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanBuildEntityMap()
    {
        $entityMap = EntityMap::fromClass(Book::class);
        $this->assertInstanceOf(EntityMap::class, $entityMap);
        $this->assertInstanceOf(InfoBlock::class, $entityMap->getAnnotation());
        $this->assertInstanceOf(ReflectionClass::class, $entityMap->getReflection());
        $this->assertContainsOnlyInstancesOf(PropertyMap::class, $entityMap->getProperties());

        foreach ($entityMap->getProperties() as $propertyMap) {
            $this->assertInstanceOf(PropertyAnnotationInterface::class, $propertyMap->getAnnotation());
            $this->assertInstanceOf(ReflectionProperty::class, $propertyMap->getReflection());
        }

        return $entityMap;
    }

    /**
     * @depends testCanBuildEntityMap
     * @param EntityMap $entityMap
     * @return EntityMap
     */
    public function testIsEntityMapCorrect(EntityMap $entityMap)
    {
        $this->assertEquals('Entity\Book', $entityMap->getClass());
        $this->assertEquals('test_entity', $entityMap->getAnnotation()->getType());
        $this->assertEquals('books', $entityMap->getAnnotation()->getCode());
        $this->assertEquals('Книги', $entityMap->getAnnotation()->getName());

        $this->assertEquals('title', $entityMap->getProperty('title')->getCode());
        $this->assertInstanceOf(Field::class, $entityMap->getProperty('title')->getAnnotation());
        $this->assertEquals('NAME', $entityMap->getProperty('title')->getAnnotation()->getCode());
        $this->assertEquals(Field::TYPE_STRING, $entityMap->getProperty('title')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('title')->getAnnotation()->isPrimaryKey());
        $this->assertEmpty($entityMap->getProperty('title')->getAnnotation()->isMultiple());

        $this->assertEquals('isShow', $entityMap->getProperty('isShow')->getCode());
        $this->assertInstanceOf(Field::class, $entityMap->getProperty('isShow')->getAnnotation());
        $this->assertEquals('ACTIVE', $entityMap->getProperty('isShow')->getAnnotation()->getCode());
        $this->assertEquals(Field::TYPE_BOOLEAN, $entityMap->getProperty('isShow')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('isShow')->getAnnotation()->isPrimaryKey());
        $this->assertEmpty($entityMap->getProperty('isShow')->getAnnotation()->isMultiple());

        $this->assertEquals('coAuthors', $entityMap->getProperty('coAuthors')->getCode());
        $this->assertInstanceOf(Property::class, $entityMap->getProperty('coAuthors')->getAnnotation());
        $this->assertEquals('co_authors', $entityMap->getProperty('coAuthors')->getAnnotation()->getCode());
        $this->assertEquals(Property::TYPE_ENTITY, $entityMap->getProperty('coAuthors')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('coAuthors')->getAnnotation()->isPrimaryKey());
        $this->assertEquals('Соавторы', $entityMap->getProperty('coAuthors')->getAnnotation()->getName());
        $this->assertTrue($entityMap->getProperty('coAuthors')->getAnnotation()->isMultiple());

        $this->assertEquals('publishedAt', $entityMap->getProperty('publishedAt')->getCode());
        $this->assertInstanceOf(Property::class, $entityMap->getProperty('publishedAt')->getAnnotation());
        $this->assertEquals('published_at', $entityMap->getProperty('publishedAt')->getAnnotation()->getCode());
        $this->assertEquals(Property::TYPE_DATETIME, $entityMap->getProperty('publishedAt')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('publishedAt')->getAnnotation()->isPrimaryKey());
        $this->assertEquals('Опубликована', $entityMap->getProperty('publishedAt')->getAnnotation()->getName());
        $this->assertEmpty($entityMap->getProperty('publishedAt')->getAnnotation()->isMultiple());

        $this->assertEquals('isBestseller', $entityMap->getProperty('isBestseller')->getCode());
        $this->assertInstanceOf(Property::class, $entityMap->getProperty('isBestseller')->getAnnotation());
        $this->assertEquals('is_bestseller', $entityMap->getProperty('isBestseller')->getAnnotation()->getCode());
        $this->assertEquals(Property::TYPE_BOOLEAN, $entityMap->getProperty('isBestseller')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('isBestseller')->getAnnotation()->isPrimaryKey());
        $this->assertEquals('Бестселлер', $entityMap->getProperty('isBestseller')->getAnnotation()->getName());
        $this->assertEmpty($entityMap->getProperty('isBestseller')->getAnnotation()->isMultiple());

        $this->assertEquals('pagesNum', $entityMap->getProperty('pagesNum')->getCode());
        $this->assertInstanceOf(Property::class, $entityMap->getProperty('pagesNum')->getAnnotation());
        $this->assertEquals('pages_num', $entityMap->getProperty('pagesNum')->getAnnotation()->getCode());
        $this->assertEquals(Property::TYPE_INTEGER, $entityMap->getProperty('pagesNum')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('pagesNum')->getAnnotation()->isPrimaryKey());
        $this->assertEquals('Кол-во страниц', $entityMap->getProperty('pagesNum')->getAnnotation()->getName());
        $this->assertEmpty($entityMap->getProperty('pagesNum')->getAnnotation()->isMultiple());

        $this->assertEquals('tags', $entityMap->getProperty('tags')->getCode());
        $this->assertInstanceOf(Property::class, $entityMap->getProperty('tags')->getAnnotation());
        $this->assertEquals('tags', $entityMap->getProperty('tags')->getAnnotation()->getCode());
        $this->assertEquals(Property::TYPE_STRING, $entityMap->getProperty('tags')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('tags')->getAnnotation()->isPrimaryKey());
        $this->assertEquals('Теги', $entityMap->getProperty('tags')->getAnnotation()->getName());
        $this->assertTrue($entityMap->getProperty('tags')->getAnnotation()->isMultiple());

        $this->assertEquals('republicationsAt', $entityMap->getProperty('republicationsAt')->getCode());
        $this->assertInstanceOf(Property::class, $entityMap->getProperty('republicationsAt')->getAnnotation());
        $this->assertEquals('republications_at', $entityMap->getProperty('republicationsAt')->getAnnotation()->getCode());
        $this->assertEquals(Property::TYPE_DATETIME, $entityMap->getProperty('republicationsAt')->getAnnotation()->getType());
        $this->assertEquals(false, $entityMap->getProperty('republicationsAt')->getAnnotation()->isPrimaryKey());
        $this->assertEquals('Переиздания', $entityMap->getProperty('republicationsAt')->getAnnotation()->getName());
        $this->assertTrue($entityMap->getProperty('republicationsAt')->getAnnotation()->isMultiple());

        $this->assertEquals('id', $entityMap->getProperty('id')->getCode());
        $this->assertInstanceOf(Field::class, $entityMap->getProperty('id')->getAnnotation());
        $this->assertEquals('ID', $entityMap->getProperty('id')->getAnnotation()->getCode());
        $this->assertEquals(Field::TYPE_INTEGER, $entityMap->getProperty('id')->getAnnotation()->getType());
        $this->assertEquals(true, $entityMap->getProperty('id')->getAnnotation()->isPrimaryKey());
        $this->assertEmpty($entityMap->getProperty('id')->getAnnotation()->isMultiple());

        return $entityMap;
    }
}