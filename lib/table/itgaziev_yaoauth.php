<?php
namespace ITGaziev\YaStock\Table;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class ITGazievYaOAuthTable extends Entity\DataManager {
    public static function getTableName() {
        return 'b_itgaziev_ya_oauth';
    }

    public static function getUfId() {
        return 'YA_OAUTH';
    }

    public static function getMap() {
        return array(
            new Entity\IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new Entity\StringField('ACTIVE', array('required' => true)),
            new Entity\StringField('NAME', array('required' => true)),

            new Entity\StringField("CLIENT_ID", array('required' => true)),
            new Entity\StringField("CLIENT_SECRET", array('required' => true)),
            new Entity\TextField('CONTENT'),
        );
    }
}