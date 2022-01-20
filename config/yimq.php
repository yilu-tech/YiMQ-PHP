<?php

return [
    "default" => "yimq",

    "connections"=>[
        "yimq"=>[
            'name' => 'user',
            "client"=>[
                'uri' => env('YIMQ_DEFALUT_SERVICE_URI'),
                'headers'=>[]
            ],
            /**
             * 消息参与处理器
             */
            'processors'=>[

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
                "processes" => 'yimq_processes'
            ]
        ]
    ]



];
