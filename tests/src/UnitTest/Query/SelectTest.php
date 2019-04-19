<?php

namespace Sheerockoff\BitrixEntityMapper\Test\UnitTest\Query;

use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Entity\Book;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;
use Sheerockoff\BitrixEntityMapper\Query\Select;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

final class SelectTest extends TestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function testCanAssertOnSchemaMissing()
    {
        self::deleteInfoBlocks();
        $this->expectException(InvalidArgumentException::class);
        Select::from(Book::class)->fetch();
    }

    /**
     * @throws ReflectionException
     */
    public function testCanNormalizeValues()
    {
        $map = [
            [null, PropertyAnnotationInterface::TYPE_STRING, null],
            ['string', PropertyAnnotationInterface::TYPE_STRING, 'string'],
            ['', PropertyAnnotationInterface::TYPE_STRING, ''],
            ['Y', PropertyAnnotationInterface::TYPE_BOOLEAN, true],
            ['1', PropertyAnnotationInterface::TYPE_BOOLEAN, true],
            ['notEmptyString', PropertyAnnotationInterface::TYPE_BOOLEAN, true],
            ['N', PropertyAnnotationInterface::TYPE_BOOLEAN, false],
            ['0', PropertyAnnotationInterface::TYPE_BOOLEAN, false],
            ['', PropertyAnnotationInterface::TYPE_BOOLEAN, false],
            [3, PropertyAnnotationInterface::TYPE_INTEGER, 3],
            ['3', PropertyAnnotationInterface::TYPE_INTEGER, 3],
            [3.14, PropertyAnnotationInterface::TYPE_FLOAT, 3.14],
            ['3.14', PropertyAnnotationInterface::TYPE_FLOAT, 3.14],
            ['notEmptyString', PropertyAnnotationInterface::TYPE_INTEGER, 0],
            [
                '2014-11-03 12:30:59',
                PropertyAnnotationInterface::TYPE_DATETIME,
                DateTime::createFromFormat('Y-m-d H:i:s', '2014-11-03 12:30:59')
            ],
            [
                '03.11.2014 12:30:59',
                PropertyAnnotationInterface::TYPE_DATETIME,
                DateTime::createFromFormat('Y-m-d H:i:s', '2014-11-03 12:30:59')
            ],
        ];

        $selectRef = new ReflectionClass(Select::class);
        $normalizeRef = $selectRef->getMethod('normalizeValue');
        $normalizeRef->setAccessible(true);

        foreach ($map as $entry) {
            list($rawValue, $type, $expected) = $entry;
            $value = $normalizeRef->invoke(null, $rawValue, $type);

            if ($type === PropertyAnnotationInterface::TYPE_DATETIME) {
                /** @var DateTime $expected */
                $this->assertInstanceOf(DateTime::class, $expected);
                /** @var DateTime $value */
                $this->assertInstanceOf(DateTime::class, $value);
                $this->assertEquals($expected->getTimestamp(), $value->getTimestamp());
            } else {
                $this->assertTrue(
                    $value === $expected,
                    "Raw value: $rawValue. Type: $type. Expected: $expected. Value: $value."
                );
            }
        }

        $normalizeRef->setAccessible(false);
    }
}