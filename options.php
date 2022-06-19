<?php
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if(!$USER->isAdmin()) $APPLICATION->authForm('Nope');

$module_id = 'itgaziev.yastock';
$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

Loc::loadMessages($context->getServer()->getDocumentRoot() . '/bitrix/modules/options.php');
Loc::loadMessages(__FILE__);

$tabControl = new CAdminTabControl("tabControl", array(
    array(
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'),
        'TITLE' => Loc::getMessage('MAIN_YAB_TITLE_RIGHTS')
    )
));

if((!empty($save) || !empty($restore)) && $request->isPost() && check_bitrix_sessid()) {
    //TODO : Save options group rights
}
?><form method="post" action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= $module_id ?>&lang=<?= LANG ?>" enctype="multipart/form-data" name="post_form"><?
$tabControl->Begin();
$tabControl->BeginNextTab();
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
echo bitrix_sessid_post();
$tabControl->Buttons();
?><input type="submit" name="Update" value="<? echo Loc::getMessage('MAIN_SAVE'); ?>"><?
?><input type="reset" name="reset" value="<? echo Loc::getMessage('MAIN_RESET'); ?>"><?
$tabControl->End();

?></form><?