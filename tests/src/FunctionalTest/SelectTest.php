<?php

namespace Sheerockoff\BitrixEntityMapper\Test\FunctionalTest;

use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Entity\Author;
use Entity\Book;
use Generator;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\Query\Select;
use Sheerockoff\BitrixEntityMapper\SchemaBuilder;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

final class SelectTest extends TestCase
{
    private static $authorIds = [];
    private static $bookIds = [];

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public static function setUpBeforeClass()
    {
        self::deleteInfoBlocks();
        self::deleteInfoBlockType();
        self::clearBitrixCache();
        self::addInfoBlockType();
        SchemaBuilder::build(EntityMap::fromClass(Author::class));
        SchemaBuilder::build(EntityMap::fromClass(Book::class));
        self::$authorIds = self::addAuthors();
        self::$bookIds = self::addBooks();
    }

    public static function tearDownAfterClass()
    {
        self::deleteInfoBlocks();
        self::deleteInfoBlockType();
        self::clearBitrixCache();
    }

    private static function addAuthors()
    {
        $iBlock = CIBlock::GetList(null, [
            '=TYPE' => 'test_entity',
            '=CODE' => 'authors',
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        self::assertNotEmpty($iBlock['ID']);

        $coAuthors = [
            ['NAME' => 'Р. Л. Стивенсон'],
            ['NAME' => 'Т. Пратчетт'],
            ['NAME' => 'Неизвестный автор'],
            ['NAME' => 'Неизвестный автор 2']
        ];

        $ids = [];
        foreach ($coAuthors as $fields) {
            $fields['IBLOCK_ID'] = $iBlock['ID'];
            $cIBlockElement = new CIBlockElement();
            $id = $cIBlockElement->Add($fields);
            self::assertNotEmpty($id, strip_tags($cIBlockElement->LAST_ERROR));
            $ids[$fields['NAME']] = $id;
        }

        return $ids;
    }

    private static function addBooks()
    {
        $iBlock = CIBlock::GetList(null, [
            '=TYPE' => 'test_entity',
            '=CODE' => 'books',
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        self::assertNotEmpty($iBlock['ID']);

        $ids = [];
        foreach (self::getBookFields() as $fields) {
            $fields['IBLOCK_ID'] = $iBlock['ID'];
            $cIBlockElement = new CIBlockElement();
            $id = $cIBlockElement->Add($fields);
            self::assertNotEmpty($id, strip_tags($cIBlockElement->LAST_ERROR));
            $ids[] = $id;
        }

        return $ids;
    }

    private static function getBookFields()
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
                'ACTIVE' => 'Y',
                'PROPERTY_VALUES' => [
                    'author' => self::$authorIds['Р. Л. Стивенсон'],
                    'co_authors' => [self::$authorIds['Неизвестный автор'], self::$authorIds['Неизвестный автор 2']],
                    'is_bestseller' => $yesPropEnum['ID'],
                    'pages_num' => 350,
                    'tags' => ['приключения', 'пираты'],
                    'published_at' => BitrixDateTime::createFromPhp(
                        DateTime::createFromFormat('d.m.Y H:i:s', '14.06.1883 00:00:00')
                    )
                ]
            ],
            [
                'NAME' => 'Цвет волшебства',
                'ACTIVE' => 'N',
                'PROPERTY_VALUES' => [
                    'author' => self::$authorIds['Т. Пратчетт'],
                    'co_authors' => false,
                    'is_bestseller' => false,
                    'pages_num' => 300,
                    'tags' => ['приключения', 'фентези'],
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
        $iteration = 0;
        foreach ($select->iterator() as $book) {
            $iteration++;
            $this->assertLessThanOrEqual(2, $iteration, 'Итератор уходит в бесконечный цикл.');
            $books[] = $book;
        }

        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);
        $this->assertNotEquals($books[0]->getId(), $books[1]->getId());
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
        $this->assertNotEquals($books[0]->getId(), $books[1]->getId());
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanFetch()
    {
        $select = Select::from(Book::class);
        $this->assertInstanceOf(Select::class, $select);

        /** @var Book[] $books */
        $books = [];
        $iteration = 0;
        while ($book = $select->fetch()) {
            $iteration++;
            $this->assertLessThanOrEqual(2, $iteration, 'Итератор уходит в бесконечный цикл.');
            $books[] = $book;
        }

        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);
        $this->assertNotEquals($books[0]->getId(), $books[1]->getId());
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testIteratorInterfaceImplementation()
    {
        $select = Select::from(Book::class);
        $this->assertInstanceOf(Select::class, $select);

        /** @var Book[] $books */
        $books = [];
        $iteration = 0;
        foreach ($select as $book) {
            $iteration++;
            $this->assertLessThanOrEqual(2, $iteration, 'Итератор уходит в бесконечный цикл.');
            $books[] = $book;
        }

        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);
        $this->assertNotEquals($books[0]->getId(), $books[1]->getId());

        $this->assertFalse($select->valid());
        $this->assertNull($select->key());
        $this->assertNull($select->current());


        $select->rewind();
        $this->assertTrue($select->valid());
        $this->assertEquals(0, $select->key());
        $this->assertInstanceOf(Book::class, $select->current());

        $select->next();
        $this->assertTrue($select->valid());
        $this->assertEquals(1, $select->key());
        $this->assertInstanceOf(Book::class, $select->current());

        $select->next();
        $this->assertFalse($select->valid());
        $this->assertNull($select->key());
        $this->assertNull($select->current());
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSelectByPrimaryKey()
    {
        foreach (self::$bookIds as $id) {
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

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('title', '%', 'ст')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);
        $this->assertCount(2, $books);

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('title', '%', 'undefined')->fetchAll();
        $this->assertCount(0, $books);

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('tags', 'фентези')->fetchAll();
        $this->assertCount(1, $books);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSelectByBoolean()
    {
        /** @var Book $book */
        $book = Select::from(Book::class)->where('isShow', true)->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(true, $book->isShow);

        /** @var Book $book */
        $book = Select::from(Book::class)->where('isShow', false)->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(false, $book->isShow);

        /** @var Book $book */
        $book = Select::from(Book::class)->where('isBestseller', true)->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(true, $book->isBestseller);

        /** @var Book $book */
        $book = Select::from(Book::class)->where('isBestseller', false)->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(false, $book->isBestseller);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSelectByDateTime()
    {
        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('publishedAt', '14.06.1883')->fetchAll();
        $this->assertCount(1, $books);
        $book = reset($books);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('14.06.1883', $book->publishedAt->format('d.m.Y'));

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('publishedAt', '%', '14.06.1883')->fetchAll();
        $this->assertCount(1, $books);
        $book = reset($books);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('14.06.1883', $book->publishedAt->format('d.m.Y'));

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('publishedAt', '<', '01.01.1900')->fetchAll();
        $this->assertCount(1, $books);
        $book = reset($books);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('14.06.1883', $book->publishedAt->format('d.m.Y'));

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('publishedAt', null)->fetchAll();
        $this->assertCount(0, $books);

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('publishedAt', new DateTime('01.09.1983 00:00:00'))->fetchAll();
        $this->assertCount(1, $books);
        $book = reset($books);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('01.09.1983', $book->publishedAt->format('d.m.Y'));

        /** @var Book[] $books */
        $books = Select::from(Book::class)->where('publishedAt', BitrixDateTime::createFromPhp(new DateTime('01.09.1983')))->fetchAll();
        $this->assertCount(1, $books);
        $book = reset($books);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('01.09.1983', $book->publishedAt->format('d.m.Y'));
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSelectByRawFilter()
    {
        /** @var Book $book */
        $book = Select::from(Book::class)->whereRaw('NAME', 'Остров сокровищ')->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('Остров сокровищ', $book->title);

        /** @var Book $book */
        $book = Select::from(Book::class)->whereRaw('NAME', '%', 'сокровищ')->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('Остров сокровищ', $book->title);
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSortByProperty()
    {
        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('pagesNum', 'asc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev <= $book->pagesNum);
            }
            $prev = $book->pagesNum;
        }

        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('pagesNum', 'desc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev >= $book->pagesNum);
            }
            $prev = $book->pagesNum;
        }
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSortByField()
    {
        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('id', 'asc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev <= $book->getId());
            }
            $prev = $book->getId();
        }

        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('id', 'desc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev >= $book->getId());
            }
            $prev = $book->getId();
        }
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSortByBooleanField()
    {
        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('isShow', 'asc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev <= $book->isShow);
            }
            $prev = $book->isShow;
        }

        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('isShow', 'desc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev >= $book->isShow);
            }
            $prev = $book->isShow;
        }
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanSortByBooleanProperty()
    {
        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('isBestseller', 'asc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev <= $book->isBestseller);
            }
            $prev = $book->isBestseller;
        }

        /** @var Book[] $books */
        $books = Select::from(Book::class)->orderBy('isBestseller', 'desc')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Book::class, $books);

        $prev = null;
        foreach ($books as $book) {
            if ($prev !== null) {
                $this->assertTrue($prev >= $book->isBestseller);
            }
            $prev = $book->isBestseller;
        }
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanReturnChildEntities()
    {
        /** @var Book $book */
        $book = Select::from(Book::class)->where('title', 'Остров сокровищ')->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertInstanceOf(Author::class, $book->author);
        $this->assertEquals('Р. Л. Стивенсон', $book->author->getName());
        $this->assertContainsOnlyInstancesOf(Author::class, $book->coAuthors);

        $coAuthorNames = array_map(function (Author $author) {
            return $author->getName();
        }, $book->coAuthors);

        $this->assertEmpty(array_diff($coAuthorNames, ['Неизвестный автор', 'Неизвестный автор 2']));
        $this->assertEmpty(array_diff(['Неизвестный автор', 'Неизвестный автор 2'], $coAuthorNames));

        /** @var Book $book */
        $book = Select::from(Book::class)->where('title', 'Цвет волшебства')->fetch();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertInstanceOf(Author::class, $book->author);
        $this->assertEquals('Т. Пратчетт', $book->author->getName());
        $this->assertContainsOnlyInstancesOf(Author::class, $book->coAuthors);

        $coAuthorNames = array_map(function (Author $author) {
            return $author->getName();
        }, $book->coAuthors);

        $this->assertEmpty(array_diff($coAuthorNames, []));
        $this->assertEmpty(array_diff([], $coAuthorNames));
    }
}