<?php

define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../bitrix/');
/** @noinspection PhpIncludeInspection */
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

require __DIR__ . '/BitrixEntityMapperTestCase.php';
require __DIR__ . '/Entity/Book.php';
