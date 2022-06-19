<?php
/*
CAgent::AddAgent(
    "CTicket::AutoClose();",  // имя функции
    "support",                // идентификатор модуля
    "N",                      // агент не критичен к кол-ву запусков
    86400,                    // интервал запуска - 1 сутки
    "",                       // дата первой проверки - текущее
    "Y",                      // агент активен
    "",                       // дата первого запуска - текущее
    30);
*/
namespace ITGaziev\YaStock;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Main\Sale;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;
use Bitrix\Catalog;

class Agent {
    /**
     * TIME
     * - 1 = 3600
     * - 2 = 10800
     * - 3 = 86400
     * - 4 = 604800
     */
    public static function addAgent($time, $id, $uid) {
        $secunds = 0;
        switch($time) {
            case 1: $secunds = 3600; break;
            case 2: $secunds = 10800; break;
            case 3: $secunds = 86400; break;
            case 4: $secunds = 604800; break;
            default: $secunds = 0;
        }
        $agent_name = "\\ITGaziev\\YaStock\\Main::RunAgent(".$id.");";
        if($uid) {
            if($secunds != 0) {
                \CAgent::Update($uid, array("AGENT_INTERVAL" => $secunds, "ACTIVE" => "Y"));
            } else {
                \CAgent::Update($uid, array("AGENT_INTERVAL" => $secunds, "ACTIVE" => "N"));
            }
        } else {
            $active = $time ? 'Y' : 'N';
            $uid = \CAgent::AddAgent($agent_name, "itgaziev.yastock", "N", $secunds, "", $active, "");
        }

        return $uid;
    }

    public static function checkAgent($uid) {
        $arAgent = \CAgent::GetList(array(), array("ID" => $uid))->Fetch();
        return $arAgent;
    }

    public static function deleteAgent() {

    }

    public static function removeAgents() {
        \CAgent::RemoveModuleAgents('itgaziev.yastock');
    }
}