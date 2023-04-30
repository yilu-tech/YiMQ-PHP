<?php


namespace YiLuTech\YiMQ\Tests\Unit;


use Orchestra\Testbench\TestCase;
use YiluTech\YiMQ\Clients\PdoClient;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Messages\TransMessage;

class TransTest extends TestCase
{
    private  PdoClient $client;
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new PdoClient('test',null,[
            "broker"=>"main",
            'table_prefix'=>'yimq',
            'address'=> '127.0.0.1:8443',
            'dsn'=> 'mysql:host=127.0.0.1;dbname=yimq',
            'username'=>'root',
            'password'=>'123456',
            'secret'=>'secret'
        ]);
        $this->client->delete('delete from users');
        $this->client->testClear();
    }


    function testBegin(){
        $this->client->mock()->transaction("test");
        $trans = $this->client->transaction("test");
        $trans->begin();
        //todo 检查delay
        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::PREPARING]);
    }

    function testCommit(){


        $this->client->mock()->transaction("test");
        $trans = $this->client->transaction("test");
        $trans->begin();
        $trans->commit();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }

    function testRollbck(){



        $this->client->mock()->transaction("test");
        $trans = $this->client->transaction("test");
        $trans->begin();
        $trans->rollback();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::CANCELED,'status'=>MessageStatus::WAITING]);
    }

    function testCallbackCommit(){

        $this->client->mock()->transaction("test");
        $result = $this->client->transaction("test",function (){
            return "success";
        })->begin();

        $this->assertEquals($result,"success");

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }

    function testCallbackRollback(){

        $this->expectExceptionMessage("failed");
        $this->client->mock()->transaction("test");
        try {
            $this->client->transaction("test",function (){
                throw new \Exception("failed");
            })->begin();

        }catch (\Exception $e){
            $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::CANCELED,'status'=>MessageStatus::WAITING]);
            throw $e;
        }
    }

    function testEc(){

        $this->client->mock()->transaction("test");
        $this->client->mock()->prepare("test");

        $result = $this->client->transaction("test",function (TransMessage $trans){
            $trans->ec("user@user.create")->data("test")->join();
            $trans->ec("user@user.create")->join();
            $trans->ec("user@user.create")->join();
            return "success";
        })->begin();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTED]);
        $this->assertDatabaseCount($this->client->messageTable(),1);
    }

    function testTcc(){

        $this->client->mock()->transaction("test");
        $this->client->mock()->prepare("test");
        $this->client->mock()->tcc("user@user.create","success");

        $this->client->transaction("test",function (TransMessage $trans){
            $tcc=  $trans->tcc("user@user.create")->data(["username"=>"test"])->try();
            $this->assertEquals($tcc->getPrepareData(),'success');
        })->begin();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }

    function testXa(){

        $this->client->mock()->transaction("test");
        $this->client->mock()->prepare("test");
        $this->client->mock()->xa("user@user.create","success");

         $this->client->transaction("test",function (TransMessage $trans){
            $xa =  $trans->xa("user@user.create")->data(["username"=>"test"])->prepare();
             $this->assertEquals($xa->getPrepareData(),'success');
        })->begin();


        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }

    function testSaga(){

        $this->client->mock()->transaction("test");
        $this->client->mock()->prepare("test");
        $this->client->mock()->saga("user@user.create","saga");

        $this->client->transaction("test",function (TransMessage $trans){
            $saga =  $trans->saga("user@user.create")->data(["username"=>"test"])->try();
            $this->assertEquals($saga->getPrepareData(),'saga');
        })->begin();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTED]);
    }



}