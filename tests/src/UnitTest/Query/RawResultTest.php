<?php

namespace Sheerockoff\BitrixEntityMapper\Test\UnitTest\Query;

use InvalidArgumentException;
use Sheerockoff\BitrixEntityMapper\Query\RawResult;
use Sheerockoff\BitrixEntityMapper\Test\TestCase;

final class RawResultTest extends TestCase
{
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
}