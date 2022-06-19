<?php

namespace ITGaziev\YaStock;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Main\Sale;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;
use Bitrix\Catalog;

class Main {
    static $module_id = 'itgaziev.yastock';

    public static function getIBlockList() {
        if(!Loader::includeModule('iblock')) return array();

        $arrIBlockTypes = array();

        $res = \CIBlock::GetList([], [], true);
        while($ar_res = $res->Fetch()) {
            $arrIBlockTypes['REFERENCE'][] = '['.$ar_res['ID'] .'] ' . $ar_res['NAME'];
            $arrIBlockTypes['REFERENCE_ID'][] = $ar_res['ID'];
        }

        return $arrIBlockTypes;
    }

    public static function getOAuthList() {
        $rsData = Table\ITGazievYaOAuthTable::getList(array(
            'select' => array('ID', 'NAME'),
            'filter' => array('ACTIVE' => 'Y'),
        ));
        $arrIBlockTypes = array();
        while($arRes = $rsData->Fetch()) {
            $arrIBlockTypes['REFERENCE'][] = '['.$arRes['ID'] .'] ' . $arRes['NAME'];
            $arrIBlockTypes['REFERENCE_ID'][] = $arRes['ID'];
        }
        return $arrIBlockTypes;
    }

    public static function getAgentTime() {
        $listTime = [];

        $listTime['REFERENCE'][0] = 'Не выгружать';
        $listTime['REFERENCE_ID'][0] = 0;

        $listTime['REFERENCE'][1] = 'Каждый час';
        $listTime['REFERENCE_ID'][1] = 1;

        $listTime['REFERENCE'][2] = 'Каждые 3 часа';
        $listTime['REFERENCE_ID'][2] = 2;

        $listTime['REFERENCE'][3] = 'Каждый день';
        $listTime['REFERENCE_ID'][3] = 3;

        $listTime['REFERENCE'][4] = 'Раз в неделю';
        $listTime['REFERENCE_ID'][4] = 4;

        return $listTime;
    }

    public static function getPriceType() {
        $listTime = [];

        $listTime['REFERENCE'][0] = 'Остатки и цены';
        $listTime['REFERENCE_ID'][0] = 0;

        $listTime['REFERENCE'][1] = 'Остатки';
        $listTime['REFERENCE_ID'][1] = 1;

        $listTime['REFERENCE'][2] = 'Цены';
        $listTime['REFERENCE_ID'][2] = 2;

        return $listTime;
    }

    public static function getFieldIblock() {
        $result[] = array('id' => 'ID', 'text' => 'ID', 'type' => 'field', 'compare' => 'id');
        $result[] = array('id' => 'NAME', 'text' => 'Название', 'type' => 'field', 'compare' => 'string');
        $result[] = array('id' => 'CODE', 'text' => 'Символьный код', 'type' => 'field', 'compare' => 'string');
        $result[] = array('id' => 'PREVIEW_TEXT', 'text' => 'Короткое описание', 'type' => 'field', 'compare' => 'string');
        $result[] = array('id' => 'DETAIL_TEXT', 'text' => 'Детальное описание', 'type' => 'field', 'compare' => 'string');
        $result[] = array('id' => 'AVAILABLE', 'text' => 'В наличии', 'type' => 'field', 'compare' => 'bool');
        $result[] = array('id' => 'ACTIVE', 'text' => 'Активный', 'type' => 'field', 'compare' => 'bool');

        return $result;
    }

    public static function getPrices() {
        $rsGroup = \Bitrix\Catalog\GroupTable::getList();
        while($arGroup=$rsGroup->fetch()) {
            //echo '<pre>'; print_r($arGroup); echo '</pre>';
            $name = $arGroup['NAME'];
            if($arGroup['BASE'] == 'Y') $name = 'Базовая цена';
            $result[] = array('id' => 'PRICE_' . $arGroup['ID'], 'text' => $name, 'type' => 'price', 'compare' => 'number', 'PRICE_ID' => $arGroup['ID'], 'PRICE_DISCOUNT' => false);
            $result[] = array('id' => 'PRICE_' . $arGroup['ID'] . '_WITHOUT_DISCOUNT', 'text' => $name . ' (со скидкой)', 'type' => 'price', 'compare' => 'number', 'PRICE_ID' => $arGroup['ID'], 'PRICE_DISCOUNT' => true);
        }

        return $result;
    }

    public static function getProperties($iblock) {
        Loader::includeModule('iblock');
        $res = \CIBlock::GetProperties($iblock, array(), array());
        $result = [];

        $arResult = [];
        while($res_ar = $res->Fetch()) {
            if($res_ar['PROPERTY_TYPE'] == 'F') continue;

            $type = 'string';
            if($res_ar['PROPERTY_TYPE'] == 'S' && !empty($res_ar['USER_TYPE_SETTINGS'])) {
                $type = 'hload';
            } else if($res_ar['PROPERTY_TYPE'] == 'S'){
                $type = 'string';
            } else if($res_ar['PROPERTY_TYPE'] == 'N') {
                $type = 'number';
            } else if($res_ar['PROPERTY_TYPE'] == 'E') {
                $type = 'element';
            } else if($res_ar['PROPERTY_TYPE'] == 'L') {
                $type = 'list';
            }
            $result[] = array(
                'id' => 'PROPERTY_' . $res_ar['ID'], 
                'text' => '[' . $res_ar['ID'] . '] ' . $res_ar['NAME'], 
                'type' => 'property', 
                'code' => $res_ar['CODE'],
                'compare' => $type
            );
        }
        return $result;
    }

    public static function getStores() {
        $resStore = \Bitrix\Catalog\StoreTable::getList([
            'select' => ['*'],
            'filter' => [
              'ACTIVE' => 'Y',
            ]
        ]);
        $result[] = array(
            'id' => 'STORE_BASE', 
            'text' => 'Общий остаток', 
            'type' => 'store', 
            'compare' => 'store',
            'STORE_CODE' => 'CATALOG_QUANTITY',
            'STORE_ID' => 'BASE',
        );
        while($arStore = $resStore->fetch()) {
            $result[] = array(
                'id' => 'STORE_' . $arStore['ID'], 
                'text' => '[' . $arStore['ID'] . '] ' . $arStore['TITLE'], 
                'type' => 'store', 
                'compare' => 'store',
                'STORE_CODE' => 'CATALOG_STORE_AMOUNT_' . $arStore['ID'],
                'STORE_ID' => $arStore['ID'],
            );
        }
        return $result;
    }

    public static function getArrayCompareType() {
        $result = [
            [
                'id' => 'string',
                'list' => [
                    ['id' => 'equal', 'text' => 'Равно'],
                    ['id' => 'not-equal', 'text' => 'Не равно'],
                    ['id' => 'like', 'text' => 'Содержит'],
                    ['id' => 'not-like', 'text' => 'Не содержит'],        
                ]
            ],[
                'id' => 'number',
                'list' => [
                    ['id' => 'equal', 'text' => 'Равно'],
                    ['id' => 'not-equal', 'text' => 'Не равно'],
                    ['id' => 'more', 'text' => 'Большше'],
                    ['id' => 'less', 'text' => 'Меньше'],       
                ]
            ],[
                'id' => 'element',
                'list' => [
                    ['id' => 'in', 'text' => 'В списке'],
                    ['id' => 'not-in', 'text' => 'Не в списке'],       
                ]
            ],[
                'id' => 'hload',
                'list' => [
                    ['id' => 'in', 'text' => 'В списке'],
                    ['id' => 'not-in', 'text' => 'Не в списке'],       
                ]
            ],[
                'id' => 'section',
                'list' => [
                    ['id' => 'in', 'text' => 'В списке'],
                    ['id' => 'not-in', 'text' => 'Не в списке'],       
                ]
            ],[
                'id' => 'list',
                'list' => [
                    ['id' => 'in', 'text' => 'В списке'],
                    ['id' => 'not-in', 'text' => 'Не в списке'],      
                ]
            ],[
                'id' => 'id',
                'list' => [
                    ['id' => 'equal', 'text' => 'Равно'],
                    ['id' => 'not-equal', 'text' => 'Не равно'],      
                ]
            ],[
                'id' => 'bool',
                'list' => [
                    ['id' => 'bool', 'text' => 'Равно'],       
                ]
            ],[
                'id' => 'store',
                'list' => [
                    ['id' => 'equal', 'text' => 'Равно'],
                    ['id' => 'not-equal', 'text' => 'Не равно'],
                    ['id' => 'more', 'text' => 'Большше'],
                    ['id' => 'less', 'text' => 'Меньше'], 
                ]
            ]
        ];

        return $result;
    }

    public static function getArraysSelectParameter($iblock) {
        $prices = self::getPrices();
        $store = self::getStores();
        $attribute = self::getProperties($iblock);

        $field = [];
        $field[] = array('id' => 'ID', 'text' => 'ID', 'type' => 'field', 'compare' => 'id');
        $field[] = array('id' => 'CODE', 'text' => 'Символьный код', 'type' => 'field', 'compare' => 'string');

        $data = [];
        foreach($field as $val) $data[] = $val;

        foreach($prices as $val) {
            $data[] = $val;
        }
        foreach($store as $val) {
            $data[] = $val;
        }

        $n_attribute = [];
        foreach($attribute as $key => $val) {
            if($val['compare'] == 'number' || $val['compare'] == 'string') {
                $data[] = $val;
                $n_attribute[] = $val;
            }
        }
        $result[] = array('id' => 'NULL', 'text' => 'Пропустить', 'type' => 'empty', 'compare' => 'null');

        $group = [
            'text' => 'Поля инфоблока',
            'children' => $field
        ];

        $result[] = $group;
        $group = [
            'text' => 'Типы цен',
            'children' => $prices
        ];

        $result[] = $group;

        $group = [
            'text' => 'Остаток',
            'children' => $store
        ];

        $result[] = $group;

        $group = [
            'text' => 'Свойства',
            'children' => $n_attribute
        ];

        $result[] = $group;

        return ['select' => $result, 'data' => $data];        
    }

    public static function getArraysSelect($iblock) {
        $field = self::getFieldIblock();
        $prices = self::getPrices();
        $store = self::getStores();
        $attribute = self::getProperties($iblock);
        
        $data = [];

        foreach($field as $val) $data[] = $val;
        foreach($prices as $val) $data[] = $val;
        foreach($store as $val) $data[] = $val;
        foreach($attribute as $val) $data[] = $val;
        $data[] = array('id' => 'ELEMENT', 'text' => 'Товар', 'type' => 'object', 'compare' => 'element');
        $data[] = array('id' => 'SECTION', 'text' => 'Раздел', 'type' => 'object', 'compare' => 'section');

        $group = [
            'text' => 'Объекты',
            'children' => [
                array('id' => 'ELEMENT', 'text' => 'Товар', 'type' => 'object', 'compare' => 'element'),
                array('id' => 'SECTION', 'text' => 'Раздел', 'type' => 'object', 'compare' => 'section')
            ]
        ];

        $result[] = $group;

        $group = [
            'text' => 'Поля инфоблока',
            'children' => $field
        ];

        $result[] = $group;

        $group = [
            'text' => 'Типы цен',
            'children' => $prices
        ];

        $result[] = $group;

        $group = [
            'text' => 'Остаток',
            'children' => $store
        ];

        $result[] = $group;

        $group = [
            'text' => 'Свойства',
            'children' => $attribute
        ];

        $result[] = $group;

        return ['select' => $result, 'data' => $data];
    }

    public static function getChangesValues($rule, $iblock, $options) {
        Loader::includeModule('iblock');
        $change = $rule['attribute'];

        if(strpos($change, 'PROPERTY_') !== false) {
            return self::getChangeProperty($rule, $iblock, $options);
        } else if($change == 'SECTION') {
            return self::getChangeSections($rule['values'], $iblock, $options);
        } else if($change == 'ELEMENT') {
            return self::getChangeElement($rule['values'], $iblock, $options);
        } else if(strpos($change, 'PRICE_') !== false) {
            return '<input type="text" name="CONDITION['.$options['group'].']['.$options['index'].'][values]" value="'.$rule['values'].'" placeholder="По строке" class="condition-input"/>';
        } else if(strpos($change, 'STORE_') !== false) {
            return '<input type="number" name="CONDITION['.$options['group'].']['.$options['index'].'][values]" value="'.$rule['values'].'" placeholder="По строке" class="condition-input"/>';
        } else if($change == 'AVAILABLE' || $change == 'ACTIVE') {
            $temp = '<select name="CONDITION[' . $options['group'] . '][' . $options['index'] . '][values]" class="condition-select condition-bool-select">';
            $temp .= $rule['values'] == 'N' ? '<option value="N" selected>Нет</option>' : '<option value="N">Нет</option>';
            $temp .= $rule['values'] == 'Y' ? '<option value="Y" selected>Да</option>' : '<option value="Y">Да</option>';
            $temp .= '</select>';
            return $temp;
        } else {
            return '<input type="text" name="CONDITION['.$options['group'].']['.$options['index'].'][values]" value="'.$rule['values'].'" placeholder="По строке" class="condition-input"/>';
        }
    }

    public static function getChangeProperty($rule, $iblock, $options) {
        $id = str_replace('PROPERTY_', '', $rule['attribute']);
        $resProp = \CIBlockProperty::GetByID($id, $iblock)->GetNext();
        $type = 'string';
        if($resProp['PROPERTY_TYPE'] == 'S' && !empty($resProp['USER_TYPE_SETTINGS'])) {
            $name = 'CONDITION[' .$options['group'].']['.$options['index'].'][values][]';
            $result = self::searchPropHLoad($resProp, $rule['values']);
            $template = '<select name="'.$name.'" class="condition-select condition-ajax-select" multiple>';
            foreach ($result as $val) 
            {
                $template .= '<option value="' . $val['id'] . '" selected="selected">[' . $val['id'] . '] ' . $val['name'] . '</option>';
            }
            $template .= '</select>';
    
            return $template;
        } else if($resProp['PROPERTY_TYPE'] == 'S'){
            return '<input type="text" name="CONDITION['.$options['group'].']['.$options['index'].'][values]" value="'.$rule['values'].'" placeholder="По строке" class="condition-input"/>';
        } else if($resProp['PROPERTY_TYPE'] == 'N') {
            return '<input type="text" name="CONDITION['.$options['group'].']['.$options['index'].'][values]" value="'.$rule['values'].'" placeholder="По строке" class="condition-input"/>';
        } else if($resProp['PROPERTY_TYPE'] == 'E') {
            $name = 'CONDITION[' .$options['group'].']['.$options['index'].'][values][]';
            $arFilter = array(
                "IBLOCK_ID" => $resProp['LINK_IBLOCK_ID'],
                "ID" => $rule['values']
            );
            $arSelect = Array("ID", "NAME");
            $res = \CIBlockElement::GetList(array(), $arFilter, false, array(), $arSelect);
            $template = '<select name="'.$name.'" class="condition-select condition-ajax-select" multiple>';
            while($ar_fields = $res->GetNext())
            {
                $template .= '<option value="' .$ar_fields['ID'] . '" selected="selected">[' . $ar_fields['ID'] . '] ' . $ar_fields['NAME']. '</option>';
            }
            $template .= '</select>';
    
            return $template;
        } else if($resProp['PROPERTY_TYPE'] == 'L') {
            $name = 'CONDITION[' .$options['group'].']['.$options['index'].'][values][]';
            $property_enums = \CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=> $iblock, "PROPERTY_ID"=>$id, "ID" => $rule['values']));

            $template = '<select name="'.$name.'" class="condition-select condition-ajax-select" multiple>';
            while ($prop = $property_enums->Fetch()) {
                $template .= '<option value="' .$prop['ID'] . '" selected="selected">[' . $prop['ID'] . '] ' . $prop['VALUE']. '</option>';
            }
            $template .= '</select>';
    
            return $template;
        }

        


    }
    public static function getChangeElement($values, $iblock, $options) {
        \Bitrix\Main\Loader::includeModule('iblock');
        $name = 'CONDITION[' .$options['group'].']['.$options['index'].'][values][]';
        $arFilter = array(
            "IBLOCK_ID" => $iblock,
            "ID" => $values
        );
        $arSelect = Array("ID", "NAME");

        $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
        
        $template = '<select name="'.$name.'" class="condition-select condition-ajax-select" multiple>';
        while($ar_fields = $res->GetNext())
        {
            $template .= '<option value="' . $ar_fields['ID'] . '" selected="selected">[' . $ar_fields['ID'] . '] ' . $ar_fields['NAME'] . '</option>';
        }

        $template .= '</select>';

        return $template;

    }

    public static function getChangeSections($values, $iblock, $options) {
        \Bitrix\Main\Loader::includeModule('iblock');
        $name = 'CONDITION[' .$options['group'].']['.$options['index'].'][values][]';
        $rsSection = \Bitrix\Iblock\SectionTable::getList(array(
            'filter' => array(
                'IBLOCK_ID' => $iblock,
                'ID' => $values,
            ), 
            'select' =>  array('ID', 'NAME'),
        ));



        $template = '<select name="'.$name.'" class="condition-select condition-ajax-select" multiple>';
        while ($arSection=$rsSection->fetch()) 
        {
            $template .= '<option value="' . $arSection['ID'] . '" selected="selected">[' . $arSection['ID'] . '] ' . $arSection['NAME'] . '</option>';
        }
        $template .= '</select>';

        return $template;
    }

    public static function searchPropHLoad($res, $values) {
        Loader::includeModule("highloadblock");
        $result = [];
        if($res && $res['USER_TYPE_SETTINGS']['TABLE_NAME']) {
            $hlblockFind = HL\HighloadBlockTable::getList([
                'filter' => ['=TABLE_NAME' => $res['USER_TYPE_SETTINGS']['TABLE_NAME']]
            ])->fetch();

            $hlbl = $hlblockFind['ID']; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
            $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch(); 

            $entity = HL\HighloadBlockTable::compileEntity($hlblock); 
            $entity_data_class = $entity->getDataClass(); 

            $rsData = $entity_data_class::getList(array(
                "select" => array("*"),
                "order" => array("ID" => "ASC"),
                "filter" => array("UF_XML_ID"=> $values),
            ));

            while($arData = $rsData->Fetch()){
                $result[] = array('id' => $arData['UF_XML_ID'], 'name' => $arData['UF_NAME']);
            }
        }
        return $result; 
    }

    public static function RunAgent($ID) {
        $r = date('Y-m-d H:i:s') . ': ID = ' .$ID . "\n";
        $path = __DIR__ . '/forcron.txt';
        file_put_contents($path, $r, FILE_APPEND);
        Export::runCron($ID);

        return "\\ITGaziev\\YaStock\\Main::RunAgent(".$ID.");";
    }
}