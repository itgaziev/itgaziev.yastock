<?php

namespace ITGaziev\YaStock;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Main\Sale;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;
use Bitrix\Catalog;

class Export {
    public static function runCron($id) {
        \CModule::IncludeModule("iblock");
        \CModule::IncludeModule("sale");
        \CModule::IncludeModule("catalog");

        $result = Table\ITGazievYaStockTable::getById($id);
        $condition = $result->fetch();
        if(!empty($condition['PARAMETERS'])) $condition['PARAMETERS'] = unserialize($condition['PARAMETERS']);
        if(!empty($condition['FILTERS'])) $condition['FILTERS'] = unserialize($condition['FILTERS']);
        
        $arFilter = self::arFilter($condition);
        $arSelect = array('ID', 'NAME', 'IBLOCK_ID', 'CODE');
        $arPricesDiscount = [];
        $arParameters = [];
        if($arParams['price_type'] == 0 || $arParams['price_type'] == 2):
            self::getPriceSelect($condition, $arSelect, $arPricesDiscount, $arParameters);
        endif;
        
        if($arParams['price_type'] == 0 || $arParams['price_type'] == 1):
            self::getOutletsSelect($condition, $arSelect, $arParameters);
        endif;

        $res = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        $arOffers = [];
        while($ar_fields = $res->GetNext())
        {
            /*******************
             * SKU
             * PRICE_DISCOUNT
             * PRICE_BASE
             * PRICE_PREMIUM
             * OUTLETS
             * - QUANTITY
             * - STORE_NAME
             *******************/
            $arItem = $ar_fields;
            
            foreach($arPricesDiscount as $value) {
                if(!empty($arItem['CATALOG_PRICE_' . $value])) {
                    $arPrice = self::applyDiscount($arItem['ID'], $arItem['CATALOG_PRICE_' . $value], 1);
                    $arItem['PRICE_' . $value . '_DISCOUNT'] = $arPrice['PRICE'];
                }
            }


            $arOffer = [];
            $arOffer['SKU'] = $arItem[$condition['PARAMETERS']['SKU']];  
            if($arParams['price_type'] == 0 || $arParams['price_type'] == 2):  
                if($arParameters['PRICE_BASE']) {
                    if(!isset($arItem[$arParameters['PRICE_BASE']]) || empty($arItem[$arParameters['PRICE_BASE']])) {
                        $arOffer['PRICE_BASE'] = '0.00';
                    } else {
                        $arOffer['PRICE_BASE'] = $arItem[$arParameters['PRICE_BASE']];
                    }
                }
                if($arParameters['PRICE_DISCOUNT']) {
                    if(!isset($arItem[$arParameters['PRICE_DISCOUNT']]) || empty($arItem[$arParameters['PRICE_DISCOUNT']])) {
                        $arOffer['PRICE_DISCOUNT'] = $arOffer['PRICE_BASE'];
                    } else {
                        $arOffer['PRICE_DISCOUNT'] = $arItem[$arParameters['PRICE_DISCOUNT']];
                    }
                }
                if($arParameters['PRICE_PREMIUM']) {
                    if(!isset($arItem[$arParameters['PRICE_PREMIUM']]) || empty($arItem[$arParameters['PRICE_PREMIUM']])) {
                        $arOffer['PRICE_PREMIUM'] = '0.00';
                    } else {
                        $arOffer['PRICE_PREMIUM'] = $arItem[$arParameters['PRICE_PREMIUM']];
                    }
                }
            endif;

            if($arParams['price_type'] == 0 || $arParams['price_type'] == 1):
                foreach($arParameters['OUTLETS'] as $outlet) {
                    $arOffer['OUTLETS'][] = [
                        'QUANTITY' => $arItem[$outlet['FIELD']],
                        'STORE_NAME' => $outlet['STORE_NAME']
                    ];
                }
            endif;

            $arOffers[] = $arOffer;
        }

        $yml = new Yml($id, $arOffers, ['type' => 'full']);
        $yml->makeFile();
    }
    public static function runAjax($arParams) {
        //echo '<pre>'; print_r($arParams); echo '</pre>';
        \CModule::IncludeModule("iblock");
        \CModule::IncludeModule("sale");
        \CModule::IncludeModule("catalog");

        $result = Table\ITGazievYaStockTable::getById($arParams['id']);
        $condition = $result->fetch();
        if(!empty($condition['PARAMETERS'])) $condition['PARAMETERS'] = unserialize($condition['PARAMETERS']);
        if(!empty($condition['FILTERS'])) $condition['FILTERS'] = unserialize($condition['FILTERS']);
        
        $arFilter = self::arFilter($condition);
        $arSelect = array('ID', 'NAME', 'IBLOCK_ID', 'CODE');

        
        if($arParams['action'] == 'total') {
            $totalres = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            $total = $totalres->SelectedRowsCount();
            $pages = ceil($total / $arParams['step_count']);

            $yml = new Yml($arParams['id'], [], ['type' => 'start']);
            $yml->makeFile();
            return ['total_count' => $total, 'page' => 1, 'action' => 'export', 'total_page' => $pages, 'per' => 0];
        } else if($arParams['action'] == 'export') {
            $arPricesDiscount = [];
            $arParameters = [];
            if($arParams['price_type'] == 0 || $arParams['price_type'] == 2):
                self::getPriceSelect($condition, $arSelect, $arPricesDiscount, $arParameters);
            endif;
            
            if($arParams['price_type'] == 0 || $arParams['price_type'] == 1):
                self::getOutletsSelect($condition, $arSelect, $arParameters);
            endif;
            
            $res = \CIBlockElement::GetList(array(), $arFilter, false, array("nPageSize"=> intval($arParams['step_count']), 'iNumPage' => $arParams['page']), $arSelect);
            $arOffers = [];
            while($ar_fields = $res->GetNext())
            {
                /*******************
                 * SKU
                 * PRICE_DISCOUNT
                 * PRICE_BASE
                 * PRICE_PREMIUM
                 * OUTLETS
                 * - QUANTITY
                 * - STORE_NAME
                 *******************/
                $arItem = $ar_fields;
                
                foreach($arPricesDiscount as $value) {
                    if(!empty($arItem['CATALOG_PRICE_' . $value])) {
                        $arPrice = self::applyDiscount($arItem['ID'], $arItem['CATALOG_PRICE_' . $value], 1);
                        $arItem['PRICE_' . $value . '_DISCOUNT'] = $arPrice['PRICE'];
                    }
                }
    

                $arOffer = [];
                $arOffer['SKU'] = $arItem[$condition['PARAMETERS']['SKU']];  
                if($arParams['price_type'] == 0 || $arParams['price_type'] == 2):  
                    if($arParameters['PRICE_BASE']) {
                        if(!isset($arItem[$arParameters['PRICE_BASE']]) || empty($arItem[$arParameters['PRICE_BASE']])) {
                            $arOffer['PRICE_BASE'] = '0.00';
                        } else {
                            $arOffer['PRICE_BASE'] = $arItem[$arParameters['PRICE_BASE']];
                        }
                    }
                    if($arParameters['PRICE_DISCOUNT']) {
                        if(!isset($arItem[$arParameters['PRICE_DISCOUNT']]) || empty($arItem[$arParameters['PRICE_DISCOUNT']])) {
                            $arOffer['PRICE_DISCOUNT'] = $arOffer['PRICE_BASE'];
                        } else {
                            $arOffer['PRICE_DISCOUNT'] = $arItem[$arParameters['PRICE_DISCOUNT']];
                        }
                    }
                    if($arParameters['PRICE_PREMIUM']) {
                        if(!isset($arItem[$arParameters['PRICE_PREMIUM']]) || empty($arItem[$arParameters['PRICE_PREMIUM']])) {
                            $arOffer['PRICE_PREMIUM'] = '0.00';
                        } else {
                            $arOffer['PRICE_PREMIUM'] = $arItem[$arParameters['PRICE_PREMIUM']];
                        }
                    }
                endif;

                if($arParams['price_type'] == 0 || $arParams['price_type'] == 1):
                    foreach($arParameters['OUTLETS'] as $outlet) {
                        $arOffer['OUTLETS'][] = [
                            'QUANTITY' => $arItem[$outlet['FIELD']],
                            'STORE_NAME' => $outlet['STORE_NAME']
                        ];
                    }
                endif;

                $arOffers[] = $arOffer;
            }
            if($arParams['page'] <= $arParams['total_page']) {
                $yml = new Yml($arParams['id'], $arOffers, ['type' => 'offer']);
                $yml->makeFile();
                
                $nextpage = $arParams['page'] + 1;
                $per = 100 * ($arParams['page'] * $arParams['step_count']) / $arParams['total_count'];

                return ['page' => $nextpage, 'action' => 'export', 'total_page' => $pages, 'per' => $per];
            } else {
                $yml = new Yml($arParams['id'], [], ['type' => 'end']);
                $yml->makeFile();

                return ['action' => 'end', 'per' => 100];
            }
        } else if($arParams['action'] == 'end') {
            $yml = new Yml($arParams['id'], [], ['type' => 'save']);
            $yml->makeFile();
            return ['action' => 'saved', 'per' => 100];
        }
    }

    private static function arFilter($condition) {
        \CModule::IncludeModule("iblock");
        \CModule::IncludeModule("sale");
        \CModule::IncludeModule("catalog");

        $arFilter = array(
            "IBLOCK_ID" => $condition['IBLOCK']
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

    public static function debug($condition) {
        \CModule::IncludeModule("iblock");
        \CModule::IncludeModule("sale");
        \CModule::IncludeModule("catalog");
        $time_start = microtime(true);

        $arFilter = array(
            "IBLOCK_ID" => $condition['IBLOCK'],
            // "INCLUDE_SUBSECTIONS" => 'Y',
            // "ID" => 140167
        );
        $arSelect = array('ID', 'NAME');

        $arPricesDiscount = [];
        $arParameters = [];
        self::getPriceSelect($condition, $arSelect, $arPricesDiscount, $arParameters);
        self::getOutletsSelect($condition, $arSelect, $arParameters);
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

        // $totalres = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        // $total = $totalres->SelectedRowsCount();

        // $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=> 800), $arSelect);
        
        // $result['total_count'] = $total;
		
        // while($ar_fields = $res->GetNext())
        // {
        //     $arItem = $ar_fields;
            
        //     //DISCOUNT PRICE
        //     foreach($arPricesDiscount as $value) {
        //         if(!empty($arItem['CATALOG_PRICE_' . $value])) {
        //             $arPrice = self::applyDiscount($arItem['ID'], $arItem['CATALOG_PRICE_' . $value], 1);
        //             $arItem['PRICE_' . $value . '_DISCOUNT'] = $arPrice['PRICE'];
        //         }
        //     }

        //     $arOffer = [];
        //     $arOffer['SKU'] = $arItem[$condition['PARAMETERS']['SKU']];

        //     if($arParameters['PRICE_BASE']) {
        //         if(!isset($arItem[$arParameters['PRICE_BASE']]) || empty($arItem[$arParameters['PRICE_BASE']])) {
        //             $arOffer['PRICE_BASE'] = '0.00';
        //         } else {
        //             $arOffer['PRICE_BASE'] = $arItem[$arParameters['PRICE_BASE']];
        //         }
        //     }
        //     if($arParameters['PRICE_DISCOUNT']) {
        //         if(!isset($arItem[$arParameters['PRICE_DISCOUNT']]) || empty($arItem[$arParameters['PRICE_DISCOUNT']])) {
        //             $arOffer['PRICE_DISCOUNT'] = $arOffer['PRICE_BASE'];
        //         } else {
        //             $arOffer['PRICE_DISCOUNT'] = $arItem[$arParameters['PRICE_DISCOUNT']];
        //         }
        //     }
        //     if($arParameters['PRICE_PREMIUM']) {
        //         if(!isset($arItem[$arParameters['PRICE_PREMIUM']]) || empty($arItem[$arParameters['PRICE_PREMIUM']])) {
        //             $arOffer['PRICE_PREMIUM'] = '0.00';
        //         } else {
        //             $arOffer['PRICE_PREMIUM'] = $arItem[$arParameters['PRICE_PREMIUM']];
        //         }
        //     }

        //     foreach($arParameters['OUTLETS'] as $outlet) {
        //         $arOffer['OUTLETS'][] = [
        //             'QUANTITY' => $arItem[$outlet['FIELD']],
        //             'STORE_NAME' => $outlet['STORE_NAME']
        //         ];
        //     }

        //     $result['results'][] = $arOffer;
        // }
        // $time_end = microtime(true);
        // $time = $time_end - $time_start;
        // echo "Количество товаров $total <br>";
        // echo "Выполнено за $time секунд\n";
        // echo '<pre>'; print_r($arParameters); echo '</pre>';
        echo '<pre>'; print_r($arFilter); echo '</pre>';
    }
    private static function getCompareString($filter) {
        switch($filter['compare']) {
            case 'not-equal':
            case 'not-in':
            case 'not-like':
                return '!';
            case 'more': return '>';
            case 'less': return '<';
            default: return '';
        }
    }
    private static function getOutletsSelect($condition, &$arSelect, &$arParameters) {
        $arStore = Main::getStores();
        $applyOutlets = $condition['PARAMETERS']['OUTLETS'];

        foreach($applyOutlets as $i => $outlet) {
            if(strpos($outlet['ID'], 'STORE_') !== false) {
                $filter = array_filter($arStore, function($value, $key) use ($outlet){
                    return $outlet['ID'] == $value['id'];
                }, ARRAY_FILTER_USE_BOTH);
                $filter = reset($filter);
                if($filter) {
                    $arSelect[] = $filter['STORE_CODE'];
                    $arParameters['OUTLETS'][] = [
                        'FIELD' => $filter['STORE_CODE'],
                        'STORE_NAME' => $outlet['NAME']
                    ];
                    unset($applyOutlets[$i]);
                }
            } else if(strpos($outlet['ID'], 'PROPERTY_') !== false) {
                $idProp = str_replace('PROPERTY_', '', $outlet['ID']);

                $arSelect[] = 'PROPERTY_' . $idProp;
                $arParameters['OUTLETS'][] = [
                    'FIELD' => 'PROPERTY_'.$idProp.'_VALUE',
                    'STORE_NAME' => $outlet['NAME']
                ];
            }
        }
    }

    private static function getPriceSelect($condition, &$arSelect, &$arPricesDiscount, &$arParameters = []) {
        //PRICE SELECT
        $arPrices = Main::getPrices();
        $applyParameters = $condition['PARAMETERS'];
        foreach($arPrices as $price) {
            $code = 'CATALOG_PRICE_' . $price['PRICE_ID'];
            if($condition['PARAMETERS']['PRICE_BASE'] == $price['id']) {
                if($price['PRICE_DISCOUNT']) {
                    $arPricesDiscount[] = $price['PRICE_ID'];
                    $arParameters['PRICE_BASE'] = 'PRICE_'.$price['PRICE_ID'].'_DISCOUNT';
                } else {
                    $arPrices[] = $price['PRICE_ID'];
                    $arParameters['PRICE_BASE'] = $code;
                }
                if(!in_array($arSelect, $code)) {
                    $arSelect[] = $code;
                }
                unset($applyParameters['PRICE_BASE']);
            } else if($condition['PARAMETERS']['PRICE_DISCOUNT'] == $price['id']) {
                if($price['PRICE_DISCOUNT']) {
                    $arPricesDiscount[] = $price['PRICE_ID'];
                    $arParameters['PRICE_DISCOUNT'] = 'PRICE_'.$price['PRICE_ID'].'_DISCOUNT';
                } else {
                    $arPrices[] = $price['PRICE_ID'];
                    $arParameters['PRICE_DISCOUNT'] = $code;
                }
                if(!in_array($arSelect, $code)) {
                    $arSelect[] = $code;
                }
                unset($applyParameters['PRICE_DISCOUNT']);
            } else if($condition['PARAMETERS']['PRICE_PREMIUM'] == $price['id']) {
                if($price['PRICE_DISCOUNT']) {
                    $arPricesDiscount[] = $price['PRICE_ID'];
                    $arParameters['PRICE_PREMIUM'] = 'PRICE_'.$price['PRICE_ID'].'_DISCOUNT';
                } else {
                    $arPrices[] = $price['PRICE_ID'];
                    $arParameters['PRICE_PREMIUM'] = $code;
                }
                if(!in_array($arSelect, $code)) {
                    $arSelect[] = $code;
                }
                unset($applyParameters['PRICE_PREMIUM']);
            }
        }
        
        //TODO: PROPERTY PRICE
        foreach($applyParameters as $key => $parameter) {

        }
    } 

    public static function applyDiscount($item_id, $price_base, $price_id = 1) {
        \CModule::IncludeModule("iblock");
        \CModule::IncludeModule("catalog");
        \CModule::IncludeModule("sale");
    
		$arDiscounts = \CCatalogDiscount::GetDiscountByProduct($item_id, array(2, 3, 5), "N", $price_id, 's1');

        $useDiscount = array();
        foreach($arDiscounts as $discount)
        {
            if(empty($useDiscount))
            {
                $useDiscount['PRIORITY'] = $discount['PRIORITY'];
                $useDiscount['VALUE'] = $discount['VALUE'];
            }
            else if($useDiscount['PRIORITY'] < $discount['PRIORITY'])
            {
                $useDiscount['PRIORITY'] = $discount['PRIORITY'];
                $useDiscount['VALUE'] = $discount['VALUE'];
            }

        }
        if($useDiscount)
        {
            $newPrice['PRICE'] = $price_base - (($price_base / 100) * intval($useDiscount['VALUE']));
        }
        else
        {
            $newPrice['PRICE'] = $price_base;
        }
        $newPrice['PRICE'] = number_format($newPrice['PRICE'], 2, '.', '');
        $newPrice['BASE_PRICE'] = $price_base;
        return $newPrice;
    
    }
}