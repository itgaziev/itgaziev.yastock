<?php
global $arParams, $arData;
?>
<? foreach($arData as $arItem) { ?>
<offer id="<?= $arItem['SKU'] ?>">
<? if($arParams['price_type'] == 0 || $arParams['price_type'] == 2) { ?>
    <price><?= $arItem['PRICE_DISCOUNT'] ?></price>
    <? if($arItem['PRICE_BASE'] != $arItem['PRICE_DISCOUNT']): ?>
    <oldprice><?= $arItem['PRICE_BASE'] ?></oldprice>
    <? endif; ?>
    <? if($arItem['PRICE_PREMIUM'] > 0): ?>
    <premium_price><?= $arItem['PRICE_PREMIUM'] ?></premium_price>
    <? endif; ?>
<? 
}
if($arParams['price_type'] == 0 || $arParams['price_type'] == 1) { ?>
    <outlets>
    <? foreach($arItem['OUTLETS'] as $outlet): ?>
        <outlet instock="<?= $outlet['QUANTITY'] ?>" warehouse_name="<?= $outlet['STORE_NAME'] ?>"></outlet>
    <? endforeach; ?>
    </outlets>
<? } ?>
</offer>
<? } ?>