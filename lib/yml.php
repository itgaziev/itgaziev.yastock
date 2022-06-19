<?php

namespace ITGaziev\YaStock;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Main\Sale;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;
use Bitrix\Catalog;

class Yml {
    protected $id;
    protected $arData;
    protected $arParams;

    public function __construct($id, $arData, $arParams) {
        $this->id = $id;
        $this->arData = $arData;
        $this->arParams = $arParams;
    }

    public function makeHead() {
        $arParams = $this->arParams;
        ob_start();
        include(dirname(__DIR__) . '/template/header.php');
        return ob_get_clean();
    }
    /**
     * PRICE_TYPE 
     * 0 = Остатки и цены
     * 1 = Остатки
     * 2 = Цены
     */
    public function makeOffers() {
        global $arParams, $arData;

        $arParams = $this->arParams;
        $arData = $this->arData;
        ob_start();
        include(dirname(__DIR__) . '/template/offers.php');
        return ob_get_clean();
    }

    public function makeFooter() {
        $arParams = $this->arParams;
        ob_start();
        include(dirname(__DIR__) . '/template/footer.php');
        return ob_get_clean();
    }

    public function makeFile() {
        if($this->arParams['type'] == 'start') {
            $header = $this->makeHead();
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/ozon_export_' . $this->id . '.xml.temp', $header);
        } else if($this->arParams['type'] == 'offer') {
            $offers = $this->makeOffers();
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/ozon_export_' . $this->id . '.xml.temp', $offers, FILE_APPEND);
        } else if($this->arParams['type'] == 'end') {
            $footer = $this->makeFooter();
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/ozon_export_' . $this->id . '.xml.temp', $footer, FILE_APPEND);
        } else if($this->arParams['type'] == 'save') {
            $contents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/ozon_export_' . $this->id . '.xml.temp');        
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/ozon_export_' . $this->id . '.xml', $contents);
        } else if($this->arParams['type'] == 'full') {
            $header = $this->makeHead();
            $offers = $this->makeOffers();
            $footer = $this->makeFooter();
            $contents = $header . $offers . $footer;
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/ozon_export_' . $this->id . '.xml', $contents);
        }
    }
}