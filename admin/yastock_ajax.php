<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
use ITGaziev\YaStock;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;

Main\Loader::includeModule('itgaziev.yastock');

echo YaStock\Ajax::init($_POST['action'], $_POST['params'], $_POST['options']);