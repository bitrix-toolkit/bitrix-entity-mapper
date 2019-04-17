<?php

use Bitrix\Main\Application;
use Bitrix\Main\Data\StaticHtmlCache;
use Bitrix\Main\SystemException;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class BitrixEntityMapperTestCase extends PhpUnitTestCase
{
    /**
     * @throws SystemException
     */
    public static function initBitrixEnvironment()
    {
        // https://habr.com/ru/post/253561/
        global $DB;
        $app = Application::getInstance();
        $con = $app->getConnection();
        $DB->db_Conn = $con->getResource();

        CModule::IncludeModule('iblock');
    }

    public static function clearBitrixCache()
    {
        BXClearCache(true);
        $GLOBALS["CACHE_MANAGER"]->CleanAll();
        $GLOBALS["stackCacheManager"]->CleanAll();
        $staticHtmlCache = StaticHtmlCache::getInstance();
        $staticHtmlCache->deleteAll();
    }

    public static function deleteInfoBlocks()
    {
        $rs = CIBlock::GetList(null, ['CHECK_PERMISSIONS' => 'N']);
        while ($infoBlock = $rs->Fetch()) {
            CIBlock::Delete($infoBlock['ID']);
        }
    }

    public static function deleteInfoBlockType($type)
    {
        $exist = CIBlockType::GetByID($type)->Fetch();
        if ($exist) {
            $isDeleted = CIBlockType::Delete($type);
            if (!$isDeleted) {
                throw new RuntimeException("Ошибка удаления типа инфоблока $type.");
            }
        }
    }

    public static function addInfoBlockType($type)
    {
        $exist = CIBlockType::GetByID($type)->Fetch();
        if (!$exist) {
            $cIBlockType = new CIBlockType();
            $isAdded = $cIBlockType->Add([
                'ID' => $type,
                'SECTIONS' => 'Y',
                'IN_RSS' => 'N',
                'LANG' => [
                    'ru' => [
                        'NAME' => $type
                    ],
                    'en' => [
                        'NAME' => $type
                    ]
                ]
            ]);

            if (!$isAdded) {
                throw new RuntimeException(strip_tags($cIBlockType->LAST_ERROR));
            }
        }
    }
}