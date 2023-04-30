<?php
namespace  YiLuTech\YiMQ\Tests\Unit;
use Orchestra\Testbench\TestCase;

use YiluTech\YiMQ\Grpc\Server\ActorInfo;
use YiluTech\YiMQ\Grpc\Server\ClientInfo;
use YiluTech\YiMQ\Grpc\Server\MessageInfo;
use YiluTech\YiMQ\Grpc\Server\MessageOptions;
use YiluTech\YiMQ\Grpc\Server\ServerClient;
use YiluTech\YiMQ\Grpc\Server\TransBeginRequest;
use YiluTech\YiMQ\YiMQServiceProvider;


class GrpcTest  extends TestCase
{


    function testClient(){


        $request = new TransBeginRequest();
        $request->setTopic("test");


        $stime=microtime(true);

        $secret = "asdfasdfasdfasdf";
        $actor = "user";
        $broker = "main";
        $sign = $broker.$actor.$secret;

        $client = new ServerClient("127.0.0.1:8443",[
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
            'update_metadata' => function($metaData) use ($actor,$broker,$secret,$sign){
                $timestamp = strval(time());
                $sign = md5($sign.$timestamp);

                $metaData['actor'] = [$actor];
                $metaData['broker'] = [$broker];
                $metaData['sign'] = [$sign];
                $metaData['timestamp'] = [$timestamp];
                return $metaData;
            }
        ]);


        list($reply, $status) = $client->TransBegin($request)->wait();
        dump((microtime(true)-$stime)*1000);
        dump($status->code,"--",$status->details);
        dump($reply->getId());

        $stime=microtime(true);
        list($reply, $status) = $client->TransBegin($request)->wait();
        dump((microtime(true)-$stime)*1000);

//        $stime=microtime(true);
//        for ($i=0;$i<10000;$i++){
//            list($reply, $status) = $client->TransBegin($request)->wait();
//        }
//        dump((microtime(true)-$stime)*1000);


        $this->assertEquals(1,1);
    }

}