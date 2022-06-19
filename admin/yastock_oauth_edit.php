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
        'TAB' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_TAB0'),
        'ICON' => 'main_user_edit',
        'TITLE' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_TAB0_TITLE')
    )
);

if($ID > 0) {
    $result = YaStock\Table\ITGazievYaOAuthTable::getById($ID);
    $condition = $result->fetch();
    if($condition['CONTENT']) $condition['CONTENT'] = unserialize($condition['CONTENT']);
    // echo '<pre>'; print_r($condition); echo '</pre>';
}

$tabControl = new CAdminTabControl('tabControl', $aTabs, false);
if($REQUEST_METHOD == 'POST' && $POST_RIGHT == 'W' && check_bitrix_sessid()) {
    $arFields = array(
        'ACTIVE'        => $_POST['ACTIVE'],
        'NAME'          => $_POST['NAME'],
        'CLIENT_ID'     => $_POST['CLIENT_ID'],
        'CLIENT_SECRET' => $_POST['CLIENT_SECRET']
    );
    if($ID > 0) {
        $arFields['CONTENT'] = serialize($_POST['CONTENT']);
        $result = YaStock\Table\ITGazievYaOAuthTable::update($ID, $arFields);
        if($result->isSuccess()) {
            $res = true;

        } else {
            $errors = $result->getErrorMessages();
            $res = false;
        }
    } else {
        $result = YaStock\Table\ITGazievYaOAuthTable::add($arFields);
        if($result->isSuccess()) {
            $ID = $result->getID();
            $res = true;

        } else {
            $errors = $result->getErrorMessages();
            $res = false;
        }
    }
}

if($ID > 0) {
    $APPLICATION->SetTitle(Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_TITLE_HEAD', ['#ID#' => $ID]));
} else {
    $APPLICATION->SetTitle(Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_CREATE_TITLE_HEAD'));
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aMenu = array(
    array(
        'TEXT' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_BACK'),
        'TITLE' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_BACK_TITLE'),
        'LINK' => 'itgaziev.yastock_oauth_list.php?lang='.LANG,
        'ICON' => 'btn_list'
    )
);

if($ID > 0) {
    $aMenu[] = array('SEPARATOR' => 'Y');

    $aMenu[] = array(
        'TEXT' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_ADD'),
        'TITLE' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_ADD_TITLE'),
        'LINK' => 'itgaziev.yastock_OAUTH_edit.php?lang='.LANG,
        'ICON' => 'btn_new'
    );

    $aMenu[] = array(
        'TEXT' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_DELETE'),
        'TITLE' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_DELETE_TITLE'),
        'LINK' => "javascript:if(confirm('" . Loc::getMessage("ITGAZIEV_YASTOCK_OAUTH_DELETE_CONF") . "')) window.location='itgaziev.yastock_oauth_list.php?ID=" . $ID . "&action=delete&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
        'ICON' => 'btn_new'
    );

    $aMenu[] = array('SEPARATOR' => 'Y');
}

$context = new CAdminContextMenu($aMenu);

$context->Show();

if($ID > 0) {
    if($_REQUEST['mess'] == 'ok') {
        CAdminMessage::ShowMessage(array(
            'MESSAGE' => Loc::getMessage('ITGAZIEV_YASTOCK_OAUTH_SAVED'),
            'TYPE' => 'OK'
        ));
    }
}

//TODO : Get Iblock List

?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?lang=<?=LANG?>" enctype="multipart/form-data" name="post_form">
<?php
echo bitrix_sessid_post();
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<tr>
    <td><?= Loc::getMessage("ITGAZIEV_YASTOCK_OAUTH_FIELD_ACTIVE") ?></td>
    <td>
        <input type="checkbox" name="ACTIVE" value="Y" <?=$condition['ACTIVE'] ? ($condition['ACTIVE'] == "Y" ? "checked" : "") : "checked"?>>
    </td>
</tr>
<? if($ID > 0): ?>
    <tr>
        <td width="40%">ID:</td>
        <td width="60%">
            <span><?= $ID ?></span>
            <input type="hidden" name="ID" value="<?= $ID ?>"/>
        </td>
    </tr>
<? endif; ?>
<tr>
    <td width="40%"><span class="required">*</span><?= Loc::getMessage("ITGAZIEV_YASTOCK_OAUTH_FIELD_NAME") ?></td>
    <td width="60%"><input type="text" name="NAME" value="<?= $condition['NAME'] ?>" size="44" maxlength="255" /></td>
</tr>
<tr>
    <td width="40%"><span class="required">*</span><?= Loc::getMessage("ITGAZIEV_YASTOCK_OAUTH_FIELD_CLIENT_ID") ?></td>
    <td width="60%"><input type="text" name="CLIENT_ID" value="<?= $condition['CLIENT_ID'] ?>" size="44" maxlength="255" /></td>
</tr>
<tr>
    <td width="40%"><span class="required">*</span><?= Loc::getMessage("ITGAZIEV_YASTOCK_OAUTH_FIELD_CLIENT_SECRET") ?></td>
    <td width="60%"><input type="text" name="CLIENT_SECRET" value="<?= $condition['CLIENT_SECRET'] ?>" size="44" maxlength="255" /></td>
</tr>
<? if($ID > 0): ?>
<tr>
    <td width="40%"></td>
    <td width="60%"><button type="button" class="yastock_oauth_autorization">Авторизация</button></td>
</tr>
<? endif; ?>
<?

$tabControl->Buttons(
    array(
      "disabled"=>($POST_RIGHT<"W"),
      "back_url"=>"itgaziev.yastock_oauth_list.php?lang=".LANG,
      
    )
);

$tabControl->End();
?></form>
<?php
//TODO : Scripts
if($ID > 0):
    ob_start();
    $params = array(
        'client_id'     => $condition['CLIENT_ID'], 
        'redirect_uri'  => $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://'.$_SERVER['SERVER_NAME'].'/bitrix/tools/itgaziev.yastock/oauth2/callback.php?ID='.$ID,
        'response_type' => 'code',
    );
    ?>
    <script>
        window.onload = () => {
            $(document).on('click', '.yastock_oauth_autorization', function(){
                window.open('https://oauth.yandex.ru/authorize?<?=http_build_query( $params )?>', 'Авторизация Яндекс', 'width=600,height=400');
            });
        }
    </script>
    <?
    $jsString = ob_get_clean();
    Asset::getInstance()->addString($jsString);
endif;
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';