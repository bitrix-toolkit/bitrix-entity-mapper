<?php

namespace Sheerockoff\BitrixEntityMapper\Test\FunctionalTest;

use _CIBElement;
use CIBlockElement;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use Exception;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\SchemaBuilder;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

final class EntityMapperTest extends TestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public static function setUpBeforeClass()
    {
        self::deleteInfoBlocks();
        self::deleteInfoBlockType('entity');
        self::clearBitrixCache();
        self::addInfoBlockType('entity', 'Библиотека');
        $schemaBuilder = new SchemaBuilder(EntityMap::fromClass(Book::class));
        $schemaBuilder->build();
    }

    /**
     * @return int
     * @throws ReflectionException
     * @throws AnnotationException
     */
    public function testCanSaveNewObject()
    {
        $book = new Book();
        $book->title = 'Остров сокровищ';
        $book->author = 'Р. Л. Стивенсон';
        $book->isBestseller = true;
        $book->pagesNum = 350;
        $book->publishedAt = DateTime::createFromFormat('d.m.Y H:i:s', '14.06.1883 00:00:00');

        $id = EntityMapper::save($book);
        $this->assertNotEmpty($id);

        return $id;
    }

    /**
     * @depends testCanSaveNewObject
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function testIsSavedCorrect($id)
    {
        $element = CIBlockElement::GetList(null, ['ID' => $id])->GetNextElement();
        $this->assertInstanceOf(_CIBElement::class, $element);

        $fields = $element->GetFields();
        $this->assertEquals($id, $fields['ID']);
        $this->assertEquals('Остров сокровищ', $fields['NAME']);

        $properties = $element->GetProperties();
        $this->assertEquals('Р. Л. Стивенсон', $properties['author']['VALUE']);
        $this->assertEquals('Y', $properties['is_bestseller']['VALUE']);
        $this->assertEquals(350, $properties['pages_num']['VALUE']);
        $this->assertEquals(
            DateTime::createFromFormat('d.m.Y H:i:s', '14.06.1883 00:00:00')->getTimestamp(),
            (new DateTime($properties['published_at']['VALUE']))->getTimestamp()
        );

        return $id;
    }

    /**
     * @depends testIsSavedCorrect
     * @param int $id
     * @return int
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanUpdateObject($id)
    {
        /** @var Book $book */
        $book = EntityMapper::select(Book::class)->where('id', $id)->fetch();
        $this->assertInstanceOf(Book::class, $book);

        $book->title = 'Цвет волшебства';
        $book->author = 'Т. Пратчетт';
        $book->isBestseller = false;
        $book->pagesNum = 300;
        $book->publishedAt = DateTime::createFromFormat('d.m.Y H:i:s', '01.09.1983 00:00:00');

        $updatedId = EntityMapper::save($book);
        $this->assertNotEmpty($updatedId);
        $this->assertEquals($id, $updatedId);

        return $id;
    }

    /**
     * @depends testCanUpdateObject
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function testIsUpdatedCorrect($id)
    {
        $element = CIBlockElement::GetList(null, ['ID' => $id])->GetNextElement();
        $this->assertInstanceOf(_CIBElement::class, $element);

        $fields = $element->GetFields();
        $this->assertEquals($id, $fields['ID']);
        $this->assertEquals('Цвет волшебства', $fields['NAME']);

        $properties = $element->GetProperties();
        $this->assertEquals('Т. Пратчетт', $properties['author']['VALUE']);
        $this->assertEquals(false, $properties['is_bestseller']['VALUE']);
        $this->assertEquals(300, $properties['pages_num']['VALUE']);
        $this->assertEquals(
            DateTime::createFromFormat('d.m.Y H:i:s', '01.09.1983 00:00:00')->getTimestamp(),
            (new DateTime($properties['published_at']['VALUE']))->getTimestamp()
        );

        return $id;
    }
}