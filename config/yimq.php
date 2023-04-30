<?php

return [

    "clients"=>[
        "default"=>[
            'name' => 'user',
            'broker'=>'main',
            "address"=>"localhost",
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
            'table'=> "yimq_messages"
        ]
    ]



];
