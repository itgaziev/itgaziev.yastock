<?php

use Bitrix\Main\Localization\Loc;

$accessLevel = (string) $APPLICATION->GetGroupRight('itgaziev.yastock');
if($accessLevel > 'D') {
    Loc::loadMessages(__FILE__);

    $ozMenu = [
        'parent_menu' => 'global_menu_marketing',
        'section' => 'itgaziev_yastock',
        'sort' => 1000,
        'text' => Loc::getMessage("ITGAZIEV_YASTOCK_MENU_MAIN"),
        'title' => Loc::getMessage("ITGAZIEV_YASTOCK_MENU_MAIN"),
        'icon' => 'itgaziev_yastock_icon',
        'items_id' => 'itgaziev_yastock_main',
        'items' => [
            [
                'text' => Loc::getMessage("ITGAZIEV_YASTOCK_MENU_PRICE"),
                'title' => Loc::getMessage("ITGAZIEV_YASTOCK_MENU_PRICE"),
                'url' => 'itgaziev.yastock_price_list.php?lang='.LANGUAGE_ID,
                'more_url' => array(
                    'itgaziev.yastock_price_edit.php'
                )
            ], [
                'text' => Loc::getMessage("ITGAZIEV_YASTOCK_MENU_OAUTH"),
                'title' => Loc::getMessage("ITGAZIEV_YASTOCK_MENU_OAUTH"),
                'url' => 'itgaziev.yastock_oauth_list.php?lang='.LANGUAGE_ID,
                'more_url' => array(
                    'itgaziev.yastock_oauth_edit.php'
                )
            ],
        ],
    ];

    return $ozMenu;

} else {
    return false;
}