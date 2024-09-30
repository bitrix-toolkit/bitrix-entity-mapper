<?php

namespace BitrixToolkit\BitrixEntityMapper\Test\FunctionalTest;

use CModule;
use BitrixToolkit\BitrixEntityMapper\Test\TestCase;

final class BitrixEnvironmentTest extends TestCase
{
    public function testCanLoadBitrixEnvironment()
    {
        $this->assertTrue(CModule::IncludeModule('iblock'), "Can't load iblock module.");
    }
}