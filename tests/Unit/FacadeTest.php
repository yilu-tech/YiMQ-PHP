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
        YiMQ::manager()->add('yimq',new PdoClient(null,'yimq_messages','yimq_processes',"127.0.0.1:8443","mysql:host=127.0.0.1;dbname=yimq", "root", "123456"));
        YiMQ::client()->getPdoClient()->testClear();
    }


    function testFacadeCommit(){


        YiMQ::transaction("test")->begin();
        YiMQ::ec("user@test")->join();
        YiMQ::ec("user@test")->join();
        YiMQ::ec("user@test")->join();
        YiMQ::commit();
        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::PREPARED]);
    }
    function testFacadeClosureTrans(){


        YiMQ::transaction("test",function (){
            YiMQ::ec("user@test")->join();
        })->begin();
        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::PREPARED]);
        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['type'=>MessageType::TRANS_EC]);
    }
    function testFacadeRollback(){

        YiMQ::transaction("test")->begin();
        YiMQ::rollback();
        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::PREPARING,'status'=>MessageStatus::WAITING]);
    }

    function testFacadeXa(){

        YiMQ::mock()->xa('user@user.create','success');
        YiMQ::transaction("test",function (){
            YiMQ::xa("user@user.create")->data("test")->prepare();
        })->begin();

        $this->assertDatabaseHas(YiMQ::client()->getPdoClient()->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }
}