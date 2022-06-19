<?php
namespace ITGaziev\YaStock\Table;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class ITGazievYaStockTable extends Entity\DataManager {
    public static function getTableName() {
        return 'b_itgaziev_yastock';
    }

    public static function getUfId() {
        return 'YA_STOCK';
    }

    public static function getMap() {
        return array(
            new Entity\IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new Entity\StringField('ACTIVE', array('required' => true)),
            new Entity\DateTimeField('TIME_CREATE', array('required' => true)),
            new Entity\StringField('NAME', array('required' => true)),

            //ID iblock bitrix
            new Entity\IntegerField("IBLOCK"),

            //ID OAUHT
            new Entity\IntegerField("OAUTH_ID"),
            new Entity\StringField("COMPANY_ID"),

            //ID iblock bitrix
            new Entity\IntegerField("PRICE_TYPE"),

            //Parameters сериализация св-тв для товара
            new Entity\TextField("PARAMETERS"),

            //Filters сериализация фильтра для выгрузки
            new Entity\TextField("FILTERS"),

            //Время выгрузки по крону или агента
            new Entity\IntegerField('AGENT_TIME'),

            //Время последний выгрузки
            new Entity\DateTimeField('LAST_TIME_UPDATE'),

            //Окончена ли выгрузка последняя Y:N
            new Entity\StringField('END_LAST'),

            //UID Files
            new Entity\StringField('UID')
        );
    }
}