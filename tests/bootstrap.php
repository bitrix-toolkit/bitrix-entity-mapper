<?php

define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../bitrix/');
/** @noinspection PhpIncludeInspection */
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

CModule::IncludeModule('iblock');

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/resources/Entity/Book.php';
require __DIR__ . '/resources/Entity/WithoutInfoBlockAnnotation.php';
require __DIR__ . '/resources/Entity/WithConflictPropertyAnnotations.php';
