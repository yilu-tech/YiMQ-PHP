<?php
namespace  YiLuTech\YiMQ\Tests\Unit;
use Orchestra\Testbench\TestCase;
use YiluTech\YiMQ\ClientManager;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Facades\YiMQ;
use YiluTech\YiMQ\Laravel\LaravelClient;
use YiluTech\YiMQ\Laravel\ServiceProvider;
use YiluTech\YiMQ\Messages\TransMessage;

class LaravelTest  extends TestCase
{
    protected LaravelClient $client;
    protected function defineEnvironment($app)
    {
        $app['config']->set('yimq.default', 'yimq');
        $app['config']->set('yimq.connections.yimq', [
            'name' => 'user',
            'address' => 'localhost',
            'db_connection' => 'mysql',
            'tables'=>[
                "message"=> "yimq_messages",
                "process" => 'yimq_processes'
            ]
        ]);

    }
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        \DB::delete('delete from users');

        /* @var $clientManager ClientManager */
        $clientManager = resolve(ClientManager::class);
        $client = $clientManager->client();
        $this->client = $client->getLaravelClient();

        $client->testClear();
    }

     function testSingleton(){
        $this->assertEquals(spl_object_id(resolve(ClientManager::class)),spl_object_id(resolve(ClientManager::class)));
        $this->assertEquals(spl_object_id(resolve(ClientManager::class)),spl_object_id(YiMQ::manager()));
    }
    function testLaravelClientCommit(){

        $trans = $this->client->transaction("test");
        $trans->begin();
        $trans->commit();
        $this->assertEquals(1,1);
        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }

    function testLaravelClientRollback(){
;
        $trans = $this->client->transaction("test");
        $trans->begin();
        $trans->rollback();
        $this->assertEquals(1,1);
        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::CANCELLING,'status'=>MessageStatus::WAITING]);
    }

    function testPdoCallbackCommit(){



        $result = $this->client->transaction("test",function (){
            return "success";
        })->begin();

        $this->assertEquals($result,"success");

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }

    function testPdoCallbackRollback(){

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

    function testPdoEc(){


        $result = $this->client->transaction("test",function (TransMessage $trans){
            $trans->ec("user@user.test")->join();
            $trans->ec("user@user.test")->join();
            $trans->ec("user@user.test")->join();
            return "success";
        })->begin();

        $this->assertDatabaseHas($this->client->messageTable(),['action'=>MessageAction::SUBMITTING]);
    }



}