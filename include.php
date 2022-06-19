<?php
use Bitrix\Main;

Main\Loader::registerAutoLoadClasses('itgaziev.yastock', [
    'ITGaziev\YaStock\Table\ITGazievYaStockTable' => '/lib/table/itgaziev_yastock.php',
    'ITGaziev\YaStock\Table\ITGazievYaOAuthTable' => '/lib/table/itgaziev_yaoauth.php',
    'ITGaziev\YaStock\Main' => '/lib/main.php',
    'ITGaziev\YaStock\MarketStock' => '/lib/market_stock.php',
]);