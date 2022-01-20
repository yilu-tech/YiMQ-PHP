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
            'message_table'=>'yimq_messages',
            'process_table'=>'yimq_processes',
            'address'=> '127.0.0.1:8443',
            'dsn'=> 'mysql:host=127.0.0.1;dbname=yimq',
            'username'=>'root',
            'password'=>'123456'
        ]);
        $this->client->delete('delete from users');
        $this->client->testClear();
    }


    function testBegin(){



        $trans = $this->client->transaction("test");
        $trans->begin();
        //todo 检查delay
        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::PREPARING]);
    }

    function testCommit(){



        $trans = $this->client->transaction("test");
        $trans->begin();
        $trans->commit();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }

    function testRollbck(){




        $trans = $this->client->transaction("test");
        $trans->begin();
        $trans->rollback();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::CANCELLING,'status'=>MessageStatus::WAITING]);
    }

    function testCallbackCommit(){


        $result = $this->client->transaction("test",function (){
            return "success";
        })->begin();

        $this->assertEquals($result,"success");

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }

    function testCallbackRollback(){

        $this->expectExceptionMessage("failed");

        try {
            $this->client->transaction("test",function (){
                throw new \Exception("failed");
            })->begin();

        }catch (\Exception $e){
            $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::CANCELLING,'status'=>MessageStatus::WAITING]);
            throw $e;
        }
    }

    function testEc(){


        $result = $this->client->transaction("test",function (TransMessage $trans){
            $trans->ec("user@user.create")->data("test")->join();
            $trans->ec("user@user.create")->join();
            $trans->ec("user@user.create")->join();
            return "success";
        })->begin();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
        $this->assertDatabaseCount($this->client->messageTable(),4);
    }

    function testTcc(){


        $this->client->mock()->tcc("user@user.create","success");

        $result = $this->client->transaction("test",function (TransMessage $trans){
            return $trans->tcc("user@user.create")->data(["username"=>"test"])->try();
        })->begin();

        $this->assertEquals($result,'success');

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }

    function testXa(){


        $this->client->mock()->xa("user@user.create","success");

        $result = $this->client->transaction("test",function (TransMessage $trans){
            return $trans->xa("user@user.create")->data(["username"=>"test"])->prepare();
        })->begin();

        $this->assertEquals($result,'success');

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }

    function testSaga(){

        $this->client->mock()->saga("user@user.create","saga");

        $result = $this->client->transaction("test",function (TransMessage $trans){
            return $trans->saga("user@user.create")->data(["username"=>"test"])->try();
        })->begin();

        $this->assertEquals($result,'saga');

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }



}