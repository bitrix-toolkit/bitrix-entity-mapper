<?php

/** @noinspection PhpUndefinedClassInspection */

namespace BitrixToolkit\BitrixEntityMapper\Test\FunctionalTest;

use CDBResult;
use CIBlock;
use CIBlockProperty;
use CIBlockPropertyEnum;
use CIBlockPropertyResult;
use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use ReflectionException;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use BitrixToolkit\BitrixEntityMapper\SchemaBuilder;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;

final class SchemaBuilderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        self::deleteInfoBlocks();
        self::deleteInfoBlockType();
        self::clearBitrixCache();
        self::deleteSites();
        self::addSites();
        self::addInfoBlockType();
    }

    public static function tearDownAfterClass(): void
    {
        self::deleteInfoBlocks();
        self::deleteInfoBlockType();
        self::deleteSites();
        self::clearBitrixCache();
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanBuildSchema()
    {
        $entityMap = EntityMap::fromClass(Book::class);
        $this->assertTrue(SchemaBuilder::build($entityMap));
    }

    /**
     * @depends testCanBuildSchema
     */
    public function testIsSchemaCorrect()
    {
        $infoBlock = CIBlock::GetList(null, [
            '=TYPE' => 'test_entity',
            '=CODE' => 'books',
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        $this->assertTrue(is_array($infoBlock));
        $this->assertArrayHasKey('ID', $infoBlock);
        $this->assertNotEmpty($infoBlock['ID']);

        $rs = CIBlockProperty::GetList(null, [
            'IBLOCK_ID' => $infoBlock['ID']
        ]);

        $this->assertInstanceOf(CIBlockPropertyResult::class, $rs);

        $properties = [];
        while ($prop = $rs->Fetch()) {
            $properties[$prop['CODE']] = $prop;
        }

        $this->assertArrayHasKey('co_authors', $properties);
        $this->assertEquals('Соавторы', $properties['co_authors']['NAME']);
        $this->assertEquals('E', $properties['co_authors']['PROPERTY_TYPE']);
        $this->assertEmpty($properties['co_authors']['USER_TYPE']);
        $this->assertEquals('Y', $properties['co_authors']['MULTIPLE']);

        $this->assertArrayHasKey('published_at', $properties);
        $this->assertEquals('Опубликована', $properties['published_at']['NAME']);
        $this->assertEquals('S', $properties['published_at']['PROPERTY_TYPE']);
        $this->assertEquals('DateTime', $properties['published_at']['USER_TYPE']);
        $this->assertEquals('N', $properties['published_at']['MULTIPLE']);

        $this->assertArrayHasKey('is_bestseller', $properties);
        $this->assertEquals('Бестселлер', $properties['is_bestseller']['NAME']);
        $this->assertEquals('L', $properties['is_bestseller']['PROPERTY_TYPE']);
        $this->assertEmpty($properties['is_bestseller']['USER_TYPE']);
        $this->assertEquals('N', $properties['is_bestseller']['MULTIPLE']);
        $this->assertEquals('C', $properties['is_bestseller']['LIST_TYPE']);

        $enumRs = CIBlockProperty::GetPropertyEnum($properties['is_bestseller']['ID']);
        $this->assertInstanceOf(CDBResult::class, $enumRs);

        $propEnum = [];
        while ($entry = $enumRs->Fetch()) {
            $propEnum[] = $entry;
        }

        $this->assertCount(1, $propEnum);
        $enumYesOption = reset($propEnum);
        $this->assertTrue(is_array($enumYesOption));
        $this->assertArrayHasKey('XML_ID', $enumYesOption);
        $this->assertEquals('Y', $enumYesOption['XML_ID']);
        $this->assertArrayHasKey('VALUE', $enumYesOption);
        $this->assertEquals('Y', $enumYesOption['VALUE']);

        $this->assertArrayHasKey('pages_num', $properties);
        $this->assertEquals('Кол-во страниц', $properties['pages_num']['NAME']);
        $this->assertEquals('N', $properties['pages_num']['PROPERTY_TYPE']);
        $this->assertEmpty($properties['pages_num']['USER_TYPE']);
        $this->assertEquals('N', $properties['pages_num']['MULTIPLE']);

        $this->assertArrayHasKey('tags', $properties);
        $this->assertEquals('Теги', $properties['tags']['NAME']);
        $this->assertEquals('S', $properties['tags']['PROPERTY_TYPE']);
        $this->assertEmpty($properties['tags']['USER_TYPE']);
        $this->assertEquals('Y', $properties['tags']['MULTIPLE']);

        $this->assertArrayHasKey('republications_at', $properties);
        $this->assertEquals('Переиздания', $properties['republications_at']['NAME']);
        $this->assertEquals('S', $properties['republications_at']['PROPERTY_TYPE']);
        $this->assertEquals('DateTime', $properties['republications_at']['USER_TYPE']);
        $this->assertEquals('Y', $properties['republications_at']['MULTIPLE']);

        $this->assertArrayHasKey('cover', $properties);
        $this->assertEquals('Обложка', $properties['cover']['NAME']);
        $this->assertEquals('F', $properties['cover']['PROPERTY_TYPE']);
        $this->assertEmpty($properties['cover']['USER_TYPE']);
        $this->assertEquals('N', $properties['cover']['MULTIPLE']);
    }

    /**
     * @depends testIsSchemaCorrect
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanRebuildSchema()
    {
        $infoBlock = CIBlock::GetList(null, [
            '=TYPE' => 'test_entity',
            '=CODE' => 'books',
            'CHECK_PERMISSIONS' => 'N'
        ])->Fetch();

        $this->assertTrue(is_array($infoBlock));
        $this->assertArrayHasKey('ID', $infoBlock);
        $this->assertNotEmpty($infoBlock['ID']);

        $isBestsellerProp = CIBlockProperty::GetList(null, [
            'IBLOCK_ID' => $infoBlock['ID'],
            'CODE' => 'is_bestseller'
        ])->Fetch();

        $this->assertNotEmpty($isBestsellerProp['ID']);
        $isDeleted = CIBlockPropertyEnum::DeleteByPropertyID($isBestsellerProp['ID']);
        $this->assertNotEmpty($isDeleted);

        $this->testCanBuildSchema();
        $this->testIsSchemaCorrect();
    }
}