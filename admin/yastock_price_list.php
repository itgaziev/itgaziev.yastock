<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Localization\Loc;
use ITGaziev\YaStock;
use Bitrix\Main\Entity\Base;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$module_id = 'itgaziev.yastock';

Loader::includeModule($module_id);

Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);

if($POST_RIGHT == 'D') $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));

$sTableID = Base::getInstance('\ITGaziev\yastock\Table\ITGazievyastockTable')->getDBTableName();
$oSort = new CAdminSorting($sTableID, 'ID', 'desc');
$lAdmin = new CAdminList($sTableID, $oSort);

//TODO : Settings before

function CheckFilter() {
    global $FilterArr, $lAdmin;
    foreach($FilterArr as $f) global $$f;

    return count($lAdmin->arFilterErrors) == 0;
}

$FilterArr = array(
    'find',
    'find_type',
    'find_id',
    'find_name'
);

$lAdmin->InitFilter($FilterArr);

if(CheckFilter()) {
    $arFilter = array(
        'ID' => ($find != "" && $find_type == 'id' ? $find : $find_id),
        'NAME' => $find_name
    );

    foreach($arFilter as $key => $value) if(empty($value)) unset($arFilter[$key]);
}

if($lAdmin->EditAction() && $POST_RIGHT == 'W') {
    foreach($FIELDS as $ID => $arFields) {
        if(!$lAdmin->IsUpdate($ID)) continue;

        $ID = IntVal($ID);

        if($ID > 0) {
            foreach($arFields as $key => $value) $arData[$key] = $value;

            //$arData['TIME_CREATE'] = new \Bitrix\Main\Type\DateTime();
            $result = YaStock\Table\ITGazievYaStockTable::update($ID, $arData);

            if(!$result->isSuccess())
                $lAdmin->AddGroupError(Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_SAVE_ERROR'), $ID);
        } else {
            $lAdmin->AddGroupError(Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_SAVE_ERROR'), $ID);
        }
    }
}

if(($arID = $lAdmin->GroupAction()) && $POST_RIGHT == 'W') {
    if($_REQUEST['action_target'] == 'selected') {
        $rsData = YaStock\Table\ITGazievYaStockTable::getList(array(
            'select' => array('ID', 'NAME', 'TIME_CREATE'),
            'filter' => $arFilter,
            'order'  => array($by => $order)
        ));

        while($arRes = $rsData->Fetch()) $arID[] = $arRes['ID'];
    }

    foreach($arID as $ID) {
        if(strlen($ID) <= 0) continue;

        $ID = IntVal($ID);

        switch($_REQUEST['action']) {
            case 'delete':
                $result = YaStock\Table\ITGazievYaStockTable::delete($ID);
                if(!$result->isSuccess()) $lAdmin->AddGroupError(Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_DELETE_ERROR'), $ID);

                break;
        }
    }
}

$rsData = YaStock\Table\ITGazievYaStockTable::getList(array(
    'select' => array('ID', 'NAME', 'TIME_CREATE'),
    'filter' => $arFilter,
    'order'  => array($by => $order)
));

$rsData = new CAdminResult($rsData, $sTableID);

$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage('rub_nav')));

$lAdmin->AddHeaders(array(
    array(
        'id'        => 'ID',
        'content'   => 'ID',
        'sort'      => 'ID',
        'align'     => 'right',
        'default'   => true
    ),
    array(
        'id'        => 'NAME',
        'content'   => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_TABLE_NAME'),
        'sort'      => 'NAME',
        'default'   => true,
    ),
    array(
        'id'        => 'TIME_CREATE',
        'content'   => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_TABLE_TIME_CREATE'),
        'sort'      => 'TIME_CREATE',
        'default'   => true
    ),
    array(
        'id'        => 'RUN_PROCESS',
        'content'   => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_TABLE_RUN_PROCESS'),
        'sort'      => '',
        'default'   => true
    ),
    array(
        'id'        => 'URL_PRICE',
        'content'   => 'Ссылка на прайс',
        'sort'      => '',
        'default'   => true
    )
));

while($arRes = $rsData->NavNext(true, 'f_')) {
    $row =& $lAdmin->AddRow($f_ID, $arRes);

    //$row->AddInputField('NAME', array('size' => 40));
    $row->AddViewField('NAME', '<a href="itgaziev.yastock_price_edit.php?ID=' . $f_ID . '&lang=' . LANG . '">' . $f_NAME . '</a>');
    $row->AddViewField('RUN_PROCESS', '<a class="adm-btn adm-btn-save" href="itgaziev.yastock_price_export.php?ID=' . $f_ID . '&lang=' . LANG . '">' . Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_TABLE_RUN_PROCESS') . '</a>');
    $row->AddViewField('URL_PRICE', '<a href="https://v-dd.ru/upload/yandex_export_' . $f_ID . '.xml">yandex_export_' . $f_ID . '.xml</a>');

    $arActions = array();

    $arActions[] = array(
        'ICON' => 'edit',
        'DEFAULT' => true,
        'TEXT' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_EDIT_BTN'),
        'ACTION' => $lAdmin->ActionRedirect('itgaziev.yastock_price_edit.php?ID=' . $f_ID . '&lang=' . LANG)
    );

    if($POST_RIGHT >= 'W') {
        $arActions[] = array(
            'ICON' => 'delete',
            'TEXT' => Loc::getMessage("ITGAZIEV_YASTOCK_PRICE_DELETE_BTN"),
            'ACTION' => "if(confirm('".Loc::getMessage("ITGAZIEV_YASTOCK_PRICE_DELETE_CONFIRM")."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
        );
    }

    $arActions[] = array('SEPARATOR' => true);

    if(is_set($arActions[count($arActions) - 1], 'SEPARATOR')) unset($arActions[count($arActions) - 1]);

    $row->AddActions($arActions);
}


$lAdmin->AddFooter(array(
    array('title' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_LIST_SELECTED'), 'value' => $rsData->SelectedRowsCount()),
    array('counter' => true, 'title' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_LIST_CHECKED'), 'value' => '0')
));

$lAdmin->AddGroupActionTable(array(
    'delete' => ""
));

$aContext = array(
    array(
        'TEXT' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_ADD_TEXT'),
        'LINK' => 'itgaziev.yastock_price_edit.php?lang='.LANG,
        'TITLE' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_ADD_TITLE'),
        'ICON' => 'btn_new'
    )
);

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_LIST_TITLE'));

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

$oFilter = new CAdminFilter(
    $sTableID . '_filter',
    array(
        'ID',
        Loc::getMessage("ITGAZIEV_YASTOCK_PRICE_FILTER_FIND_NAME")
    )
);

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");