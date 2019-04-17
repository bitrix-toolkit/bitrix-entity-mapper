<?php

use Bitrix\Main\SystemException;

final class BitrixEnvironmentTest extends BitrixEntityMapperTestCase
{
    /**
     * @throws SystemException
     */
    public function testCanLoadBitrixEnvironment()
    {
        self::initBitrixEnvironment();
        $this->assertTrue(CModule::IncludeModule('iblock'), "Can't load iblock module.");
    }
}