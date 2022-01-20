<?php
namespace  YiLuTech\YiMQ\Tests\Unit;
use Orchestra\Testbench\TestCase;
use YiluTech\YiMQ\Clients\PdoClient;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageProcessAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Helpers;
use \YiluTech\YiMQ\Laravel\YiMQFacade;
use YiluTech\YiMQ\Tests\Processors\UserCreateEcProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateTransEcProcessor;
use YiluTech\YiMQ\Tests\Processors\UserCreateTransTccProcessor;

class TransEcProcessorTest  extends TestCase
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


    function testEcSubmit(){


        $processorManager = $this->client->processorManager()->init([
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
        $processorManager->process($context);

        $this->assertDatabaseMissing($this->client->messageTable(),["relation_id"=>$context['id']]);
        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::SUBMITTED]);
        $this->assertDatabaseHas('users',['username'=>$context['data']['username']]);
    }

    function testEcCancel(){


        $processorManager = $this->client->processorManager()->init([
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
        $this->expectExceptionMessage("error_test");

        try {

            $processorManager->process($context);
        }catch (\Exception $e){
            $this->assertDatabaseMissing($this->client->messageTable(),["relation_id"=>$context['id']]);
            $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::PREPARING]);
            $this->assertDatabaseCount('users',0);
            throw $e;
        }
    }

    function testTransEcSubmit(){


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
        $this->assertEquals($result,'success');

        $this->assertDatabaseHas($this->client->messageTable(),["relation_id"=>$context['id'],'action'=>MessageAction::SUBMITTING]);
        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::SUBMITTED]);

    }

    function testTransTccTryFailed(){

        $processorManager = $this->client->processorManager()->init([
            'user.create'=> UserCreateTransTccProcessor::class
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

        $this->expectExceptionMessage("error_test");
        try {

            $processorManager->process($context);
        }catch (\Exception $e){
            $this->assertDatabaseHas($this->client->messageTable(),[
                "relation_id"=>$context['id'],
                'action'=>MessageAction::CANCELLING,
                'status'=>MessageStatus::WAITING,
            ]);

            $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::PREPARING]);
            $this->assertDatabaseCount('users',0);
            throw $e;
        }
    }

    function testTransTccTryAfterSubmit(){

        $processorManager = $this->client->processorManager()->init([
            'user.create'=> UserCreateTransTccProcessor::class
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
        $this->assertEquals($result,'success');

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
        $this->assertEquals($result['message'],'succeed');

        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::SUBMITTED]);
        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::SUBMITTING,
            'status'=>MessageStatus::WAITING
        ]);
    }

    function testTransTccTryAfterCancel(){

        $processorManager = $this->client->processorManager()->init([
            'user.create'=> UserCreateTransTccProcessor::class
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
        $this->assertEquals($result,'success');

        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::PREPARED,
            'status'=>MessageStatus::DELAYED,
            'available_at'=>null
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
        $this->assertEquals($result['message'],'succeed');

        $this->assertDatabaseHas($this->client->processTable(),['id'=>$context['id'],'action'=>MessageAction::CANCELED]);
        $this->assertDatabaseHas($this->client->messageTable(),[
            "relation_id"=>$context['id'],
            'action'=>MessageAction::CANCELLING,
            'status'=>MessageStatus::WAITING
        ]);
    }
}