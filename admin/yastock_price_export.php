<?php
use ITGaziev\YaStock;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Main\Loader::includeModule('itgaziev.yastock');

Loc::loadMessages(__FILE__);

if($ID > 0) {
    $result = YaStock\Table\ITGazievYaStockTable::getById($ID);
    $condition = $result->fetch();
    if(!empty($condition['PARAMETERS'])) $condition['PARAMETERS'] = unserialize($condition['PARAMETERS']);
    if(!empty($condition['FILTERS'])) $condition['FILTERS'] = unserialize($condition['FILTERS']);

} else {
    LocalRedirect("/bitrix/admin/itgaziev.yastock_price_list.php?lang=".LANG);
}

$arJsConfig = array(
    //TODO : add js / css to header
    'jquery-ui' => array(
        'js' => '/bitrix/themes/.default/itgaziev.yastock/js/jquery-ui.min.js',
        'css' => '/bitrix/themes/.default/itgaziev.yastock/css/jquery-ui.min.css',
        'rel' => array()
    ),
    'jquery-ui-theme' => array(
        'css' => '/bitrix/themes/.default/itgaziev.yastock/css/jquery-ui.theme.min.css',
        'rel' => array()
    ),
    'jquery-select2' => array(
        'css' => '/bitrix/themes/.default/itgaziev.yastock/select2/css/select2.min.css',
        'js' => '/bitrix/modules/itgaziev.yastock/install/themes/.default/itgaziev.yastock/select2/js/select2.js?v=' . time(),
        //'js' => '/bitrix/themes/.default/itgaziev.yastock/select2/js/select2.js?v=' . time(),
        'rel' => array()
    ),
    'itgaziev.yastock' => array(
        //'js' => '/bitrix/js/itgaziev.yastock/main.js',
        'js' => '/bitrix/modules/itgaziev.yastock/install/assets/js/main.js',
        'css' => '/bitrix/modules/itgaziev.yastock/install/themes/.default/itgaziev.yastock.css',
        'rel' => array()
    ),
);

foreach($arJsConfig as $ext => $arExt) \CJSCore::RegisterExt($ext, $arExt);

CJSCore::Init(array('jquery'));
if($arJsConfig) {
    CUtil::InitJSCore(array_keys($arJsConfig));
}

$POST_RIGHT = $APPLICATION->GetGroupRight('itgaziev.yastock');

if($POST_RIGHT == 'D') $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));

$aTabs = array(
    array(
        'DIV' => 'edit0',
        'TAB' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_EXPORT_TAB0'),
        'ICON' => 'main_user_edit',
        'TITLE' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_EXPORT_TAB0_TITLE')
    )
);

$tabControl = new CAdminTabControl('tabControl', $aTabs, false);
$APPLICATION->SetTitle(Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_EXPORT_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aMenu = array(
    array(
        'TEXT' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_BACK'),
        'TITLE' => Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_BACK_TITLE'),
        'LINK' => 'itgaziev.yastock_price_list.php?lang='.LANG,
        'ICON' => 'btn_list'
    )
);

?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?lang=<?=LANG?>" enctype="multipart/form-data" name="post_form">
<?php
echo bitrix_sessid_post();
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<tr>
    <td>Кол. товаров за шаг</td>
    <td><input type="number" name="step_count" value="800" /></td>
</tr>
<tr>
    <td>Ссылка на прайс</td>
    <td><input type="text" name="price_url" value="https://v-dd.ru/upload/ozon_export_<?= $ID ?>.xml" readonly style="width: 100%;"/></td>
</tr>
<tr>
    <td>Процесс</td>
    <td>
        <div class="myProgress">
            <div class="myBar"></div>
        </div>
    </td>
</tr>
<?
//TODO : html info price
$tabControl->Buttons();
echo '<input type="submit" name="cancel" value="Вернуться" onclick="top.window.location=\'itgaziev.yastock_price_list.php?lang='. LANG . '\'" title="' . Loc::getMessage('ITGAZIEV_YASTOCK_PRICE_CANCEL') . '">';
echo '<input type="button" name="export" value="Выполнить" title="Выполнить" class="adm-btn-save btn-export">';

$tabControl->End();
ob_start();
?></form>
<script>
    window.onload = () => {
        const itgaziev_export = new ITGazievExport({});
        $(document).on('click', '.btn-export', function(){
            itgaziev_export.ajaxExport({
                action : 'total',
                id : <?= $ID ?>,
                price_type : <?= $condition['PRICE_TYPE'] ?>,
                step_count : $('input[name="step_count"]').val(),
                page : 0,
            });
        })
    }
</script>
<?

$jsString = ob_get_clean();
Asset::getInstance()->addString($jsString);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';