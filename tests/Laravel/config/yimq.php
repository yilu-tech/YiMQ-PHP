<?php

use YiluTech\YiMQ\Tests\Processors\UserCreateEcProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateTransEcProcessor;

return [
    "default" => "yimq",

    "connections"=>[
        "yimq"=>[
            'name' => 'user',
            'db_connection' => 'mysql',
            'address' => 'localhost',
            'route' => [
                'path' => 'yimq',//url前缀
                'name' =>'internal@test.yimq',//路由名称
            ],
            /**
             * 消息参与处理器
             */
            'processors'=>[
                'UserCreate'=> UserCreateEcProcessor::class,
                'UserTransCreate'=> UserCreateTransEcProcessor::class
            ],
            /**
             * 消息事件监听器
             */
            'broadcast_topics' => [
                'user.xa.create' => [
                    'allows'=>[]
                ]
            ],
            'broadcast_listeners'=>[
                \Tests\Services\UserUpdateListenerProcessor::class => 'user@user.ec.update',
            ],
            'tables'=>[
                "message"=> "yimq_messages",
                "process" => 'yimq_processes'
            ]
        ]
    ]



];
