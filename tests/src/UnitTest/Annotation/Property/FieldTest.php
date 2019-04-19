<?php

namespace Sheerockoff\BitrixEntityMapper\Test\UnitTest\Annotation\Property;

use ReflectionClass;
use ReflectionException;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

final class FieldTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testGetTypeByCode()
    {
        $map = [
            'ID' => Field::TYPE_INTEGER,
            'XML_ID' => Field::TYPE_STRING,
            'NAME' => Field::TYPE_STRING,
            'SORT' => Field::TYPE_INTEGER,
            'ACTIVE' => Field::TYPE_BOOLEAN,
            'DATE_ACTIVE_FROM' => Field::TYPE_DATETIME,
            'DATE_ACTIVE_TO' => Field::TYPE_DATETIME,
            'PREVIEW_PICTURE' => Field::TYPE_FILE,
            'DETAIL_PICTURE' => Field::TYPE_FILE,
            'PREVIEW_TEXT' => Field::TYPE_STRING,
            'DETAIL_TEXT' => Field::TYPE_STRING
        ];

        $fieldRef = new ReflectionClass(Field::class);
        $methodRef = $fieldRef->getMethod('getTypeByCode');
        $methodRef->setAccessible(true);

        foreach ($map as $code => $expectedType) {
            $type = $methodRef->invoke(null, $code);
            $this->assertEquals($expectedType, $type);
        }

        $methodRef->setAccessible(false);
    }
}