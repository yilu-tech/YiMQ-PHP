<?php

namespace  YiluTech\YiMQ\Tests\Feature;

use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Facades\YiMQ;
use YiluTech\YiMQ\Laravel\LaravelClient;
use YiluTech\YiMQ\Messages\TransMessage;
use YiluTech\YiMQ\Tests\TestCase;

class RealTest extends TestCase
{
    protected LaravelClient $client;
    function setUp(): void
    {
        parent::setUp();
        YiMQ::client()->testClear();
    }



    function testTransRollback(){

        $trans = YiMQ::transaction("order.create")->begin();
        YiMQ::ec("order@test")->data(["username"=>"jack"])->join();
        YiMQ::prepare();
        YiMQ::rollback();
        $this->assertDatabaseHas(YiMQ::client()->messageTable(),["id"=>$trans->getId(),'action'=>MessageAction::CANCELED]);
    }


    function testTransAndEc(){
        $stime=microtime(true);
        $result =  YiMQ::transaction("order.create",function (TransMessage $trans){
            YiMQ::ec("user@UserCreateTransEc")->data(["username"=>"jack"])->join();
            YiMQ::ec("user@UserCreateTransEc")->data(["username"=>"jack"])->join();

        })->begin();
        dump((microtime(true)-$stime)*1000);
        sleep(1);
        $this->assertDatabaseHas(YiMQ::client()->processTable(),['processor'=>'UserCreateTransEc','action'=>MessageAction::SUBMITTED]);

    }

    function testTransAndTcc(){

        $result =  YiMQ::transaction("order.create",function (TransMessage $trans){
            YiMQ::tcc("user@UserCreateTcc")->data(["username"=>"jack"])->try();
            $this->assertDatabaseHas(YiMQ::client()->processTable(),['processor'=>'UserCreateTcc','action'=>MessageAction::PREPARED]);
        })->begin();

        sleep(1);
        $this->assertDatabaseHas(YiMQ::client()->processTable(),['processor'=>'UserCreateTcc','action'=>MessageAction::SUBMITTED]);

    }

    function testTransAndTccRollback(){

        $this->expectExceptionMessage("test-error");

        $result =  YiMQ::transaction("order.create",function (TransMessage $trans){
            YiMQ::tcc("user@UserCreateTcc")->data(["username"=>"jack"])->try();
            $this->assertDatabaseHas(YiMQ::client()->processTable(),['processor'=>'UserCreateTcc','action'=>MessageAction::PREPARED]);
            throw new \Exception("test-error");
        })->begin();

        sleep(1);
        $this->assertDatabaseHas(YiMQ::client()->processTable(),['processor'=>'UserCreateTcc','action'=>MessageAction::CANCELED]);
    }

}
