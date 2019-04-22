<?php

namespace Sheerockoff\BitrixEntityMapper\Test;

use Bitrix\Main\Data\StaticHtmlCache;
use CIBlock;
use CIBlockType;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use RuntimeException;

abstract class TestCase extends PhpUnitTestCase
{
    /**
     * Исключает ошибки Bitrix при формировании
     * запросов к базе данных.
     *
     * @var bool
     */
    protected $backupGlobals = false;

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
        $rs = CIBlock::GetList(null, ['TYPE' => 'test_entity', 'CHECK_PERMISSIONS' => 'N']);
        while ($infoBlock = $rs->Fetch()) {
            CIBlock::Delete($infoBlock['ID']);
        }
    }

    /**
     * @throws RuntimeException
     */
    public static function deleteInfoBlockType()
    {
        $type = 'test_entity';
        $exist = CIBlockType::GetByID($type)->Fetch();
        if ($exist) {
            $isDeleted = CIBlockType::Delete($type);
            if (!$isDeleted) {
                throw new RuntimeException("Ошибка удаления типа инфоблока $type.");
            }
        }
    }

    /**
     * @throws RuntimeException
     */
    public static function addInfoBlockType()
    {
        $type = 'test_entity';
        $name = 'Тестирование EntityMapper';
        $exist = CIBlockType::GetByID($type)->Fetch();
        if (!$exist) {
            $cIBlockType = new CIBlockType();
            $isAdded = $cIBlockType->Add([
                'ID' => $type,
                'SECTIONS' => 'Y',
                'IN_RSS' => 'N',
                'LANG' => [
                    'ru' => [
                        'NAME' => $name
                    ]
                ]
            ]);

            if (!$isAdded) {
                throw new RuntimeException(strip_tags($cIBlockType->LAST_ERROR));
            }
        }
    }
}