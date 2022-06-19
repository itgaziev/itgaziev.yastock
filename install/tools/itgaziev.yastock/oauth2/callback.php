<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use ITGaziev\YaStock;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Bitrix\Main\CIBlock;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;


Main\Loader::includeModule('itgaziev.yastock');
session_start();
$ID = $_GET['ID'];

if(isset($_GET['code'])) {
    $result = YaStock\Table\ITGazievYaOAuthTable::getById($ID);
    $condition = $result->fetch();

    $query = array(
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'client_id'     => $condition['CLIENT_ID'],
        'client_secret' => $condition['CLIENT_SECRET']
    );

    $query = http_build_query( $query );
        
    // Формирование заголовков POST-запроса
    $header = "Content-type: application/x-www-form-urlencoded";

    // Выполнение POST-запроса
    $opts    = array(
        'http' =>
            array(
                'method'  => 'POST',
                'header'  => $header,
                'content' => $query
            )
    );
    $context = stream_context_create( $opts );

    if ( ! $content = @file_get_contents( 'https://oauth.yandex.ru/token', false, $context ) ) {
        $error = error_get_last();
        throw new Exception( 'HTTP request failed. Error: ' . $error['message'] );
    }

    $response = json_decode( $content );
            
    // Если при получении токена произошла ошибка
    if ( isset( $response->error ) ) {
        throw new Exception( 'При получении токена произошла ошибка. Error: ' . $response->error . '. Error description: ' . $response->error_description );
    }
    
    $arResult['content'] = json_decode($content, true);
    
    $requestedScope = array('market:partner-api');

    // Сохраняем токен в сессии
    $tokens = [
        'TOKEN_TYPE' => $arResult['content']['token_type'],
        'ACCESS_TOKEN' => $arResult['content']['access_token'],
        'REFRESH_TOKEN' => $arResult['content']['refresh_token'],
        'EXPIRES_AT' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time() + (int)$arResult['content']['expires_in']),
        'SCOPE' => '/' . implode('/', $requestedScope) . '/',
    ];

    $condition['CONTENT'] = serialize($tokens);
    $result = YaStock\Table\ITGazievYaOAuthTable::update($ID, $condition);

    if($result->isSuccess()) {
        $res = true;
        echo 'success';
    } else {
        $errors = $result->getErrorMessages();
        echo $errors;
        $res = false;
    }
}