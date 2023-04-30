<?php
namespace  YiLuTech\YiMQ\Tests\Unit;
use Orchestra\Testbench\TestCase;
use YiluTech\YiMQ\ClientManager;
use YiluTech\YiMQ\Clients\PdoClient;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageProcessAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Helpers;
use \YiluTech\YiMQ\Laravel\YiMQFacade;
use YiluTech\YiMQ\Tests\Processors\UserCreateChildTransTccProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateEcProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateTransEcProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateTransTccProcessor;

class TransEcProcessorTest  extends TestCase
{
    private  PdoClient $client;
    private ClientManager $clientManager;
    protected function setUp(): void
    {
        parent::setUp();

        $this->clientManager = new ClientManager();

        $this->client = new PdoClient('test',null,[
            'broker'=>'main',
            'table_prefix'=>'yimq',
            'address'=> '127.0.0.1:8443',
            'dsn'=> 'mysql:host=127.0.0.1;dbname=yimq',
            'username'=>'root',
            'password'=>'123456',
            'secret'=> "123456"
        ]);

        $this->client->delete('delete from users');
        $this->client->testClear();

        $this->clientManager->add('test',$this->client);
    }


    function testEcSubmit(){


        $this->client->processorManager()->init([
            'user.create'=> UserCreateEcProcessor::class
        ]);

        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::SUBMIT,
            'type' => MessageType::TRANS_EC,
            'id' => Helpers::ksuid(),
            'trans_id' => Helpers::ksuid(),
            'producer' => '1',
            'data' => [
                'username'=>rand(0,9999)
            ]
        ];
        $this->client->processorManager()->process($context);

        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::SUBMITTED]);
        $this->assertDatabaseHas('users',['username'=>$context['data']['username']]);
    }

    function testEcCancel(){


        $this->client->processorManager()->init([
            'user.create'=> UserCreateEcProcessor::class
        ]);

        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::SUBMIT,
            'type' => MessageType::TRANS_EC,
            'id' => Helpers::ksuid(),
            'trans_id' => Helpers::ksuid(),
            'producer' => '1',
            'data' => [
                'username'=>"error_test"
            ]
        ];


        $reply = $this->client->processorManager()->process($context);
        $this->assertEquals($reply['error'],"error_test");
        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::PREPARING]);
        $this->assertDatabaseCount('users',0);

    }

    function testTransEcSubmit(){

        $this->client->mock()->transaction("ec_child_trans");
        $this->client->mock()->prepare("ec_child_trans");
        $processorManager = $this->client->processorManager()->init([
            'user.create'=> UserCreateTransEcProcessor::class
        ]);
        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::SUBMIT,
            'type' => MessageType::TRANS_EC,
            'id' => Helpers::ksuid(),
            'trans_id' => Helpers::ksuid(),
            'producer' => '1',
            'data' => [
                'username'=>rand(0,9999)
            ]
        ];
        $result = $processorManager->process($context);
        $this->assertEquals($result['message'],'succeed');

        $this->assertDatabaseHas($this->client->messageTable(),["relation_id"=>$context['id'],'action'=>MessageAction::SUBMITTED]);
        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::SUBMITTED]);

    }

    function testTransTccTryFailed(){
        $this->client->mock()->transaction("trans_test");
        $this->client->mock()->prepare("trans_test");
        $processorManager = $this->client->processorManager()->init([
            'user.create'=> UserCreateChildTransTccProcessor::class
        ]);
        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::PREPARE,
            'type' => MessageType::TRANS_TCC,
            'id' => Helpers::ksuid(),
            'trans_id' => Helpers::ksuid(),
            'producer' => '1',
            'data' => [
                'username'=>"error_test"
            ]
        ];


        $reply = $processorManager->process($context);
        $this->assertEquals($reply['error'],"error_test");
        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::CANCELED,
            'status'=>MessageStatus::WAITING,
        ]);

        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::PREPARING]);
        $this->assertDatabaseCount('users',0);
    }

    function testTransTccTryAfterSubmit(){
        $this->client->mock()->transaction("trans_test");
        $this->client->mock()->prepare("trans_test");
        $processorManager = $this->client->processorManager()->init([
            'user.create'=> UserCreateChildTransTccProcessor::class
        ]);
        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::PREPARE,
            'type' => MessageType::TRANS_TCC,
            'id' => Helpers::ksuid(),
            'trans_id' => Helpers::ksuid(),
            'producer' => '1',
            'data' => [
                'username'=>rand(0,9999)
            ]
        ];
        $result = $processorManager->process($context);
        $this->assertEquals($result['message'],'prepare_succeed');

        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::PREPARED,
            'status'=>MessageStatus::DELAYED,
            'available_at'=>null
        ]);
        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::PREPARED]);

        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::SUBMIT,
            'type' => MessageType::TRANS_TCC,
            'id' => $context['id'],
            'trans_id' => $context['trans_id'],
            'producer' => '1',
            'data' => [
                'username'=>rand(0,9999)
            ]
        ];

        $result = $processorManager->process($context);
        $this->assertEquals($result['message'],'submit_succeed');

        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::SUBMITTED]);
        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::SUBMITTED,
        ]);
    }

    function testTransTccTryAfterCancel(){

        $processorManager = $this->client->processorManager()->init([
            'user.create'=> UserCreateChildTransTccProcessor::class
        ]);
        $this->client->mock()->transaction("trans_test");
        $this->client->mock()->prepare("trans_test");
        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::PREPARE,
            'type' => MessageType::TRANS_TCC,
            'id' => Helpers::ksuid(),
            'trans_id' => Helpers::ksuid(),
            'producer' => '1',
            'data' => [
                'username'=>rand(0,9999)
            ]
        ];
        $result = $processorManager->process($context);

        $this->assertEquals($result['message'],'prepare_succeed');

        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::PREPARED,
        ]);
        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::PREPARED]);

        $context = [
            'processor' => 'user.create',
            'action'=>MessageProcessAction::CANCEL,
            'type' => MessageType::TRANS_TCC,
            'id' => $context['id'],
            'trans_id' => $context['trans_id'],
            'producer' => '1',
            'data' => [
                'username'=>rand(0,9999)
            ]
        ];

        $result = $processorManager->process($context);
        $this->assertEquals($result['message'],'cancel_succeed');

        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::CANCELED]);
        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::CANCELED,
        ]);
    }
}