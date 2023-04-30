<?php

use YiluTech\YiMQ\Tests\Processors\UserCreateEcProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateTransEcProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateTransTccProcessor;
return [
    "default" => "user",
    'route' => [
        'prefix' => 'yimq',//url前缀
        'name' =>'internal@test.yimq',//路由名称
    ],
    "actors"=>[
        "user"=>[
            'broker' => 'main',
            'secret' => "asdfasdfasdfasdf",
            'db_connection' => 'mysql',
            'address' => 'localhost:8443',
            /**
             * 消息参与处理器
             */
            'processors'=>[
                'UserCreateEc'=> UserCreateEcProcessor::class,
                'UserCreateTransEc'=> UserCreateTransEcProcessor::class,
                'UserCreateTcc'=> UserCreateTransTccProcessor::class,
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
            'table_prefix' => 'yimq'
        ]
    ]



];
