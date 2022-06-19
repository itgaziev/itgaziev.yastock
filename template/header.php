<?php
$date = date(DATE_ATOM, time());
echo '<?xml version="1.0" encoding="utf-8"?>';
echo '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">';
?>
<yml_catalog date="<?= $date ?>">
    <shop>
        <name>v-dd.ru</name>
        <company>Гипермаркет товаров для ремонта - Все для дома</company>
        <url>https://v-dd.ru</url>
        <version>2.3.4</version>
        <offers>