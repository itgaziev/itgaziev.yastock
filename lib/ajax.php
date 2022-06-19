<?php
namespace ITGaziev\YaStock;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;

class Ajax {
    
    static $action;
    static $params;
    static $options;

    static $result;
    
    public static function init($action, $params, $options) {
        self::$action = $action;
        self::$params = $params['data'];
        self::$options = $options;

        switch($action) {
            case 'search':
                self::search();
                break;
            case 'export':
                // self::$result = $_POST;
                self::export();
                break;
        }

        return json_encode(self::$result);
    }

    public static function export() {
        self::$result = Export::runAjax($_POST['params']);
    }

    public static function search() {
        if(self::$options['select']['id'] == 'ELEMENT') {
            self::searchProduct();
        } else if(self::$options['select']['id'] == 'SECTION') {
            self::searchSection();
        } else if(self::$options['select']['type'] == 'property') {
            switch(self::$options['select']['compare']) {
                case 'list':
                    self::searchPropList();
                    break;
                case 'element':
                    self::searchPropElement();
                    break;
                case 'hload':
                    self::searchPropHLoad();
                    break;
            }
        }
    }

    public static function searchProduct() {
        Loader::includeModule("iblock");

        $arFilter = array(
            "IBLOCK_ID" => self::$options['iblock'],
            array(
                "LOGIC" => "OR",
                array("ID" => self::$params['q'] . '%'),
                array("NAME" => self::$params['q'] . '%'),
            ),
        );
        $arSelect = Array("ID", "NAME");

        $totalres = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        $total = $totalres->SelectedRowsCount();

        $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=> 10, 'iNumPage' => self::$params['page']), $arSelect);
        
        $result['total_count'] = $total;

        while($ar_fields = $res->GetNext())
        {
            $result['results'][] = ['id' => $ar_fields['ID'], 'name' => '[' . $ar_fields['ID'] . '] ' . $ar_fields['NAME']];
        }

        self::$result = $result; 
    }

    public static function searchSection() {
        Loader::includeModule("iblock");

        $arFilter = array(
            "IBLOCK_ID" => self::$options['iblock'],
            "NAME" =>  '%' . self::$params['q'] . '%',
        );
        $arSelect = array("ID", "NAME");
        $result = [];

        $res = \CIBlockSection::GetList(array(), $arFilter, false, $arSelect);

        while($ar_fields = $res->GetNext())
        {
            $result['results'][] = array('id' => $ar_fields['ID'], 'name' => '[' . $ar_fields['ID'] . '] ' . $ar_fields['NAME']);
        }

        self::$result = $result; 
    }

    public static function searchPropList() {
        $id = str_replace('PROPERTY_', '', self::$options['select']['id']);
        $res = \CIBlockProperty::GetByID($id, self::$options['iblock'])->GetNext();
        $result['results'] = [];
        if($res && $res['PROPERTY_TYPE'] == 'L') {
            $property_enums = \CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>self::$options['iblock'], "PROPERTY_ID"=>$id, "VALUE" => "%".self::$params['q']."%"));

            while ($prop = $property_enums->Fetch()) {
                $result['results'][] = array('id' => $prop['ID'], 'name' => $prop['VALUE']);
            }
        }

        self::$result = $result; 
    }

    public static function searchPropElement() {
        $id = str_replace('PROPERTY_', '', self::$options['select']['id']);
        $resProp = \CIBlockProperty::GetByID($id, self::$options['iblock'])->GetNext();
        $result['results'] = [];
        if($resProp && $resProp['PROPERTY_TYPE'] == 'E') {
            $arFilter = array(
                "IBLOCK_ID" => $resProp['LINK_IBLOCK_ID'],
                array(
                    "LOGIC" => "OR",
                    array("ID" => self::$params['q'] . '%'),
                    array("NAME" => self::$params['q'] . '%'),
                ),
            );
            $arSelect = Array("ID", "NAME");
    
            $totalres = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            $total = $totalres->SelectedRowsCount();
    
            $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=> 10, 'iNumPage' => self::$params['page']), $arSelect);
            
            $result['res'] = $resProp;
            $result['total_count'] = $total;
    
            while($ar_fields = $res->GetNext())
            {
                $result['results'][] = ['id' => $ar_fields['ID'], 'name' => '[' . $ar_fields['ID'] . '] ' . $ar_fields['NAME']];
            }
        }
        self::$result = $result; 
    }

    public static function searchPropHLoad() {
        Loader::includeModule("highloadblock");
        $id = str_replace('PROPERTY_', '', self::$options['select']['id']);

        $res = \CIBlockProperty::GetByID($id, self::$options['iblock'])->GetNext();
        $result['results'] = [];
        if($res && $res['USER_TYPE_SETTINGS']['TABLE_NAME']) {
            $hlblockFind = HL\HighloadBlockTable::getList([
                'filter' => ['=TABLE_NAME' => $res['USER_TYPE_SETTINGS']['TABLE_NAME']]
            ])->fetch();

            $hlbl = $hlblockFind['ID']; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
            $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch(); 

            $entity = HL\HighloadBlockTable::compileEntity($hlblock); 
            $entity_data_class = $entity->getDataClass(); 
            $offset = 0;
            if(self::$params['page'] > 1) {
                $offset = self::$params['page'] * 10;
            }
            $rsData = $entity_data_class::getList(array(
                "select" => array("*"),
                "order" => array("ID" => "ASC"),
                "filter" => array("UF_NAME"=>"%" . self::$params['q'] . "%"),
                "limit" => 10,
                "offset" => $offset
            ));

            while($arData = $rsData->Fetch()){
                $result['results'][] = array('id' => $arData['UF_XML_ID'], 'name' => $arData['UF_NAME']);
            }
        }
        self::$result = $result; 
    }
}