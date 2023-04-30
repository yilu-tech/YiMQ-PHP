<?php
namespace YiluTech\YiMQ\Facades;


use YiluTech\YiMQ\ClientManager;
use YiluTech\YiMQ\Messages\Trans\TransEc;
use YiluTech\YiMQ\Messages\TransMessage;
use YiluTech\YiMQ\Mocker;

class YiMQ
{
    private static ClientManager $clientManager;

    public static function init(){
        self::$clientManager = new ClientManager();
    }
    public static function manager():ClientManager{
        return self::$clientManager;
    }

    public static function transaction(string $topic=null,callable $callback=null):TransMessage{
        $defaultClient = self::$clientManager->client();
        return $defaultClient->transaction($topic,$callback);
    }

    public static function prepare(){
        $defaultClient = self::$clientManager->client();
        $defaultClient->prepare();
    }

    public static function commit(){
        $defaultClient = self::$clientManager->client();
        $defaultClient->commit();
    }
    public static function rollback(){
        $defaultClient = self::$clientManager->client();
        $defaultClient->rollback();
    }

    public static function ec(string $processor){
        $defaultClient = self::$clientManager->client();
        return $defaultClient->ec($processor);
    }
    public static function xa(string $processor){
        $defaultClient = self::$clientManager->client();
        return $defaultClient->xa($processor);
    }
    public static function tcc(string $processor){
        $defaultClient = self::$clientManager->client();
        return $defaultClient->tcc($processor);
    }
    public static function saga(string $processor){
        $defaultClient = self::$clientManager->client();
        return $defaultClient->saga($processor);
    }

    public static function mock(){
        $defaultClient = self::$clientManager->client();
        return $defaultClient->mock();
    }

    public static function client($name=null){
        return self::$clientManager->client($name);
    }


}