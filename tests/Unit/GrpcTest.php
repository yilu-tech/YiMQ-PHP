<?php
namespace  YiLuTech\YiMQ\Tests\Unit;
use Orchestra\Testbench\TestCase;
use YiluTech\YiMQ\YiMQServiceProvider;
use YiluTech\YiMQ\Grpc\Services\TryRequest;
use YiluTech\YiMQ\Grpc\Services\ServerClient;

class GrpcTest  extends TestCase
{


    function testClient(){
        $req = new TryRequest();
        $req->setTransId("trans");
        $req->setData("test-data");
        $stime=microtime(true);
        $client = new ServerClient("127.0.0.1:8443",[
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $stime=microtime(true);
        list($Id, $status) = $client->TccTry($req)->wait();

        dump((microtime(true)-$stime)*1000);
        dump($Id->getResult());

        $stime=microtime(true);
        list($Id, $status) = $client->TccTry($req)->wait();
        dump((microtime(true)-$stime)*1000);

        $stime=microtime(true);
        list($Id, $status) = $client->TccTry($req)->wait();
        dump((microtime(true)-$stime)*1000);
        for ($i=0;$i<10000;$i++){
            $stime=microtime(true);
            list($Id, $status) = $client->TccTry($req)->wait();
            dump((microtime(true)-$stime)*1000);
        }


        $this->assertEquals(1,1);
    }

}