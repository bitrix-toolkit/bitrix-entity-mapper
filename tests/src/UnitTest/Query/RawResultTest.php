<?php

namespace BitrixToolkit\BitrixEntityMapper\Test\UnitTest\Query;

use DateTime;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface;
use BitrixToolkit\BitrixEntityMapper\Map\PropertyMap;
use BitrixToolkit\BitrixEntityMapper\Query\RawResult;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;
use stdClass;

final class RawResultTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testCanAssert()
    {
        $rawResultRef = new ReflectionClass(RawResult::class);
        $assertRef = $rawResultRef->getMethod('assert');
        $assertRef->setAccessible(true);
        $this->expectException(InvalidArgumentException::class);
        $assertRef->invoke(null, false, 'Test assertion.');
    }

    /**
     * @throws ReflectionException
     */
    public function testNoAssertOnSuccess()
    {
        $this->expectNotToPerformAssertions();
        $rawResultRef = new ReflectionClass(RawResult::class);
        $assertRef = $rawResultRef->getMethod('assert');
        $assertRef->setAccessible(true);
        $assertRef->invoke(null, true, 'Test assertion.');
    }

    public function testCanAssertOnGetField()
    {
        $rawResult = new RawResult(1, 2, ['title' => 'Остров сокровищ', 'author' => 'Р. Л. Стивенсон']);
        $this->expectException(InvalidArgumentException::class);
        $rawResult->getField('undefined');
    }

    public function testCanGetField()
    {
        $rawResult = new RawResult(1, 2, ['title' => 'Остров сокровищ', 'author' => 'Р. Л. Стивенсон']);
        $this->assertEquals(1, $rawResult->getId());
        $this->assertEquals(2, $rawResult->getInfoBlockId());
        $this->assertEquals('Остров сокровищ', $rawResult->getField('title'));
        $this->assertEquals('Р. Л. Стивенсон', $rawResult->getField('author'));
        $this->assertTrue(is_array($rawResult->getData()));
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

        $rawResultRef = new ReflectionClass(RawResult::class);
        $normalizeRef = $rawResultRef->getMethod('normalizeValue');
        $normalizeRef->setAccessible(true);

        foreach ($map as $entry) {
            list($rawValue, $type, $expected) = $entry;

            $object = new stdClass();
            $propAnnotation = new Property(['type' => $type, 'code' => $type]);
            $object->{$propAnnotation->getCode()} = $rawValue;
            $propRef = new ReflectionProperty($object, $propAnnotation->getCode());
            $propertyMap = new PropertyMap($propAnnotation->getCode(), $propAnnotation, $propRef);

            $value = $normalizeRef->invoke(null, $propertyMap, $rawValue);

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