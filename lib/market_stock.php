<?php
namespace ITGaziev\YaStock;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Main\Sale;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;
use Bitrix\Catalog;

class MarketStock {
    
    public static function init($arStocks) {
        self::getIDs($arStocks);
    }

    public static function getIDs($arStocks) {
        $rsData = Table\ITGazievYaStockTable::getList(array(
            'select' => array('*'),
            'filter' => array('ACTIVE' => 'Y'),
        ));
        
        $arIds = array_keys($arStocks);

        while($condition = $rsData->Fetch()) {
            if(!empty($condition['PARAMETERS'])) $condition['PARAMETERS'] = unserialize($condition['PARAMETERS']);
            if(!empty($condition['FILTERS'])) $condition['FILTERS'] = unserialize($condition['FILTERS']);

            if(!$condition['OAUTH_ID']) return false;
            if(!$condition['COMPANY_ID']) return false;

            $res = Table\ITGazievYaOAuthTable::getById($condition['OAUTH_ID']);
            $oauth = $res->fetch();
            $content = unserialize($oauth['CONTENT']);
            $arFilter = self::getConditionFilter($condition, $arIds);
            $data = self::filterItems($arFilter, $condition['PARAMETERS']['WAREHOUSE_ID'], $arStocks);
            if($data) {
                $arResult[$condition['ID']]['COMPANY_ID'] = $condition['COMPANY_ID'];
                $arResult[$condition['ID']]['ACCESS_TOKEN'] = $content['ACCESS_TOKEN'];

                $arResult[$condition['ID']]['DATA'] = $data;
            }
        }
    }


    public static function filterItems($arFilter, $warehouseId, $arStocks) {
        $arSelect = array('ID');

        $res = \CIBlockElement::GetList(array(), $arFilter, false, array(), $arSelect);
        $arOffers = [];
        while($arFields = $res->GetNext()) {
            $arOffers['skus'][] = ['sku' => $arFields['ID'], 'warehouseId' => $warehouseId, ['items' => [
                    ['type' => 'FIT', 'count' => $arStocks[$arFields['ID']]['COUNTER'], 'updatedAt' => date("c", time())]
                ]
            ]];
        }

        return $arOffers;
    }

    private static function getConditionFilter($condition, $arIds) {
        \CModule::IncludeModule("iblock");
        \CModule::IncludeModule("sale");
        \CModule::IncludeModule("catalog");

        $arFilter = array(
            "IBLOCK_ID" => $condition['IBLOCK'],
            "ID" => $arIds
        );

        $arGroupFilters = array('LOGIC' => 'OR');
        foreach($condition['FILTERS'] as $group) {
            $arGroupFilter = [];
            foreach($group as $filter) {
                $compString = self::getCompareString($filter);
                if(in_array($filter['compare'], array('like', 'not-like'))) {
                    $filter['values'] = '%' . $filter['values'] . '%';
                }
                if(strpos($filter['attribute'], 'PRICE_') !== false) {
                    $idPrice = str_replace('PRICE_', '', $filter['attribute']);
                    $arGroupFilter[$compString . 'CATALOG_PRICE_' . $idPrice] = $filter['values'];
                } else if(strpos($filter['attribute'], 'PROPERTY_') !== false) {
                    $idProp = str_replace('PROPERTY_', '', $filter['attribute']);
                    $arGroupFilter[$compString . 'PROPERTY_' . $idProp] = $filter['values'];
                } else if($filter['attribute'] == 'SECTION') {
                    $arGroupFilter['INCLUDE_SUBSECTIONS'] = "Y";
                    $arGroupFilter[$compString . 'SECTION_ID'] = $filter['values'];
                } else if($filter['attribute'] == 'ELEMENT') {
                    $arGroupFilter['ID'] = $filter['values'];
                } else if($filter['attribute'] == 'STORE_BASE') {
                    $arGroupFilter[$compString . 'CATALOG_QUANTITY'] = $filter['values'];
                } else if(strpos($filter['attribute'], 'STORE_') !== false) {
                    $idStore = str_replace('STORE_', '', $filter['attribute']);
                    $arGroupFilter[$compString . 'CATALOG_STORE_AMOUNT_' . $idStore] = $filter['values'];
                } else if($filter['attribute'] == 'AVAILABLE') {
                    $arGroupFilter[$compString . 'CATALOG_AVAILABLE'] = $filter['values'];
                } else {
                    $arGroupFilter[$filter['attribute']] = $filter['values'];
                }
            }
            $arGroupFilters[] = $arGroupFilter;
        }

        $arFilter[] = $arGroupFilters;

        return $arFilter;
    }

    private static function getCompareString($filter) {
        switch($filter['compare']) {
            case 'not-equal':
            case 'not-in':
            case 'not-like': return '!';
            case 'more': return '>';
            case 'less': return '<';
            default: return '';
        }
    }
}