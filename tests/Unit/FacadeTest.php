<?php


namespace YiLuTech\YiMQ\Tests\Unit;


use Orchestra\Testbench\TestCase;
use YiluTech\YiMQ\Clients\PdoClient;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Facades\YiMQ;

class FacadeTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        YiMQ::init();
        $client = new PdoClient('test',null,[
            "broker"=>"main",
            'table_prefix'=>'yimq',
            'address'=> '127.0.0.1:8443',
            'dsn'=> 'mysql:host=127.0.0.1;dbname=yimq',
            'username'=>'root',
            'password'=>'123456',
            'secret'=>'secret'
        ]);
        YiMQ::manager()->add('yimq',$client);
        YiMQ::manager()->setDefault("yimq");
        YiMQ::client()->getPdoClient()->testClear();
    }


    function testFacadeCommit(){
        YiMQ::mock()->transaction("test");
        YiMQ::mock()->prepare("test");

        YiMQ::transaction("test")->begin();
        YiMQ::ec("user@test")->join();
        YiMQ::commit();
        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }
    function testFacadeClosureTrans(){
        YiMQ::mock()->transaction("test");
        YiMQ::mock()->prepare("test");


        YiMQ::transaction("test",function (){
            YiMQ::ec("user@test")->join();
        })->begin();
        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }
    function testFacadeRollback(){
        YiMQ::mock()->transaction("test");
        YiMQ::mock()->prepare("test");

        YiMQ::transaction("test")->begin();
        YiMQ::rollback();
        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::CANCELED]);
    }

    function testFacadeXa(){
        YiMQ::mock()->transaction("test");
        YiMQ::mock()->prepare("test");
        YiMQ::mock()->xa('user@user.create','success');
        YiMQ::transaction("test",function (){
            YiMQ::xa("user@user.create")->data("test")->prepare();
        })->begin();

        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }
}