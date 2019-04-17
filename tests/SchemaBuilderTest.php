<?php

use Bitrix\Main\SystemException;
use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Sheerockoff\BitrixEntityMapper\SchemaBuilder;

final class SchemaBuilderTest extends BitrixEntityMapperTestCase
{
    /**
     * @throws SystemException
     */
    public static function setUpBeforeClass()
    {
        self::initBitrixEnvironment();
        self::deleteInfoBlocks();
        self::deleteInfoBlockType('entity');
        self::clearBitrixCache();
        self::addInfoBlockType('entity');
    }

    /**
     * @throws ReflectionException
     * @throws AnnotationException
     */
    public function testCanBuildSchema()
    {
        $schemaBuilder = new SchemaBuilder(EntityMap::fromClass(Book::class));
        $this->assertTrue($schemaBuilder->build());
    }
}