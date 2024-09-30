<?php

namespace BitrixToolkit\BitrixEntityMapper\Test;

use Bitrix\Main\Data\StaticHtmlCache;
use Bitrix\Main\Localization\CultureTable;
use CIBlock;
use CIBlockType;
use CSite;
use LogicException;
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

    public static function addSites()
    {
        $cultures = [];
        foreach (CultureTable::getList([])->fetchAll() as $culture) {
            $cultures[$culture['CODE']] = $culture;
        }

        if (empty($cultures['en'])) {
            throw new LogicException('Culture [en] error.');
        }

        if (empty($cultures['ru'])) {
            throw new LogicException('Culture [ru] error.');
        }

        $sites = [];
        $rs = CSite::GetList($by, $order);
        while ($site = $rs->Fetch()) {
            $sites[$site['ID']] = $site;
        }

        if (empty($sites['p1'])) {
            $cSite = new CSite();
            $cSite->Add([
                'LID' => 'p1',
                'ACTIVE' => 'Y',
                'NAME' => 'Тестовая компания',
                'DIR' => '/',
                'CHARSET' => 'UTF-8',
                'LANGUAGE_ID' => 'ru',
                'CULTURE_ID' => $cultures['ru']['ID'],
            ]);
        }

        if (empty($sites['p2'])) {
            $cSite = new CSite();
            $cSite->Add([
                'LID' => 'p2',
                'ACTIVE' => 'Y',
                'NAME' => 'Test company',
                'DIR' => '/',
                'CHARSET' => 'UTF-8',
                'LANGUAGE_ID' => 'en',
                'CULTURE_ID' => $cultures['en']['ID'],
            ]);
        }
    }

    public static function deleteSites()
    {
        CSite::Delete('p1');
        CSite::Delete('p2');
    }
}