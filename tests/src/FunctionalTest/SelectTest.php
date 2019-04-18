<?php

namespace Sheerockoff\BitrixEntityMapper\Test\FunctionalTest;

use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use Generator;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\Query\Select;
use Sheerockoff\BitrixEntityMapper\SchemaBuilder;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

final class SelectTest extends TestCase
{
    private static $ids = [];

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
        self::$ids = self::addElements();
    }

    public static function addElements()
    {
        $iBlock = CIBlock::GetList(null, [
            '=TYPE' => 'entity',
            '=CODE' => 'books',
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        self::assertNotEmpty($iBlock['ID']);

        $ids = [];
        foreach (self::getElementsFields() as $fields) {
            $fields['IBLOCK_ID'] = $iBlock['ID'];
            $cIBlockElement = new CIBlockElement();
            $id = $cIBlockElement->Add($fields);
            self::assertNotEmpty($id, strip_tags($cIBlockElement->LAST_ERROR));
            $ids[] = $id;
        }

        return $ids;
    }

    private static function getElementsFields()
    {
        $yesPropEnum = CIBlockProperty::GetPropertyEnum(
            'is_bestseller',
            null,
            ['XML_ID' => 'Y', 'VALUE' => 'Y']
        )->Fetch();

        self::assertNotEmpty($yesPropEnum['ID']);

        return [
            [
                'NAME' => 'Остров сокровищ',
                'PROPERTY_VALUES' => [
                    'author' => 'Р. Л. Стивенсон',
                    'is_bestseller' => $yesPropEnum['ID'],
                    'pages_num' => 350,
                    'published_at' => BitrixDateTime::createFromPhp(
                        DateTime::createFromFormat('d.m.Y H:i:s', '14.06.1883 00:00:00')
                    )
                ]
            ],
            [
                'NAME' => 'Цвет волшебства',
                'PROPERTY_VALUES' => [
                    'author' => 'Т. Пратчетт',
                    'is_bestseller' => false,
                    'pages_num' => 300,
                    'published_at' => BitrixDateTime::createFromPhp(
                        DateTime::createFromFormat('d.m.Y H:i:s', '01.09.1983 00:00:00')
                    )
                ]
            ]
        ];
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanIterateResult()
    {
        $select = Select::from(Book::class);
        $this->assertInstanceOf(Select::class, $select);
        $this->assertInstanceOf(Generator::class, $select->iterator());

        /** @var Book[] $books */
        $books = [];
        foreach ($select->iterator() as $book) {
            $books[] = $book;
        }

        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanFetchAll()
    {
        $select = Select::from(Book::class);
        $this->assertInstanceOf(Select::class, $select);
        $books = $select->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanFetch()
    {
        $select = Select::from(Book::class);
        $this->assertInstanceOf(Select::class, $select);

        $books = [];
        while ($book = $select->fetch()) {
            $books[] = $book;
        }

        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSelectByPrimaryKey()
    {
        foreach (self::$ids as $id) {
            $select = Select::from(Book::class)->where('id', $id);
            $this->assertInstanceOf(Select::class, $select);
            /** @var Book $book */
            $book = $select->fetch();
            $this->assertInstanceOf(Book::class, $book);
            $this->assertEquals($id, $book->getId());
        }
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSelectBySubstring()
    {
        /** @var Book $book */
        $book = Select::from(Book::class)->where('title', '%', 'сокров')->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('Остров сокровищ', $book->title);

        /** @var Book $book */
        $book = Select::from(Book::class)->where('author', '%', 'пратчет')->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('Т. Пратчетт', $book->author);

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('title', '%', 'ст')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('title', '%', 'undefined')->fetchAll();
        $this->assertCount(0, $books);
    }
}