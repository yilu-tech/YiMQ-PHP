<?php
namespace YiluTech\YiMQ\Messages;

use YiluTech\YiMQ\Clients\Client;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Grpc\Server\ClientInfo;
use YiluTech\YiMQ\Grpc\Server\MessageInfo;
use YiluTech\YiMQ\Grpc\Server\MessageOptions;
use YiluTech\YiMQ\Grpc\Server\TransActionRequest;
use YiluTech\YiMQ\Grpc\Server\TransBeginRequest;
use YiluTech\YiMQ\Grpc\Server\TransPrepareRequest;
use YiluTech\YiMQ\Helpers;
use YiluTech\YiMQ\Messages\Trans\TransChildMessage;
use YiluTech\YiMQ\Messages\Trans\TransEc;
use YiluTech\YiMQ\Messages\Trans\TransSaga;
use YiluTech\YiMQ\Messages\Trans\TransTcc;
use YiluTech\YiMQ\Messages\Trans\TransXa;

class TransMessage extends Message
{
    protected $callback;
    public string $type = MessageType::TRANS;
    protected array $preparingChildren = [];

    protected string $relation_id;
    public function __construct(
        public Client $client,
        protected string $topic,
        callable $callback = null
    )
    {
        $this->callback = $callback;
        $this->action = MessageAction::PREPARING;
    }
    public function getTopic():string{
        return $this->topic;
    }

    public function restore($data){
        $this->id = $data['id'];
        $this->relation_id = $data['relation_id'];
        $this->type = $data['type'];
        $this->topic = $data['topic'];
        $this->action = $data['action'];
        $this->status = $data['status'];
    }
    public function childTransInit($relationId){
        $this->relation_id = $relationId;
    }

    public function begin($localBegin=true){
        $this->beginTrans();
        if($localBegin){
            $this->client->localBegin();
        }

        if(is_null($this->callback)){
            return $this;
        };

        try {
            $result = call_user_func($this->callback,$this);
            $this->client->commit();
            return $result;
        }catch (\Exception $e){
            $this->client->rollback();
            throw $e;
        }
    }

    private function beginTrans(){

        $this->id  = $this->beginServerTrans();

        $now = Helpers::getNow();

        $data["id"] = $this->id;
        $data["relation_id"] = isset($this->relation_id) ? $this->relation_id : null;
        $data["type"] = $this->type;
        $data["topic"] = $this->topic;
        $data["action"] = MessageAction::PREPARING;
        $data["status"] = 'DELAYED';
        $data["delay"] = 50;
        $data["attempts"] = 9;
        $data["created_at"] = Helpers::formartTime($now);
        $available_at = Helpers::addSeconds($now,$data["delay"]);
        $data["available_at"] = Helpers::formartTime($available_at);

        $this->client->transBeginTransaction($data);
    }

    private function beginServerTrans(){

        if(isset($this->client->mocker) &&  $id = $this->client->mocker->matchTransaction($this->topic,MessageAction::PREPARING)){
            return $id;
        }
        $this->client->initGrpc();

        $request = new TransBeginRequest();
        $request->setTopic($this->topic);

        list($reply, $status) = $this->client->grpcClient->TransBegin($request)->wait();
        if($status->code != 0){
            throw new SystemException("grpc call failed: ($status->code) $status->details");
        }
        return $reply->getId();
    }


    public function prepare($commitAutoPrepare = false){
        if (count($this->preparingChildren) > 0){
            $this->serverTransPrepare();
        }

        if(!$commitAutoPrepare){ //commit里执行的时候，可以省略这步的执行
            $data["action"] = MessageAction::PREPARED;
            $data["status"] = MessageStatus::DELAYED; //保持状态
            $data["available_at"] = null; //暂停超时检测
            $data["id"] = $this->id;
            $this->client->transCommit($data);
        }
        $this->action = MessageAction::PREPARED;
    }

    private function serverTransPrepare(){

        if(isset($this->client->mocker) &&  $id = $this->client->mocker->matchTransaction($this->topic,MessageAction::PREPARED)){
            return null;
        }

        $this->client->initGrpc();

        $request = new TransPrepareRequest();
        $request->setMessageId($this->id);

        $messages = [];
        /* @var $child TransChildMessage */
        foreach ($this->preparingChildren as $child){
            $messages[] = $child->getPrepareData();
        }
        $request->setMessages($messages);
        list($reply, $status) = $this->client->grpcClient->TransPrepare($request)->wait();
        if($status->code != 0){
            throw new SystemException("prepare children faild: ($status->code) $status->details");
        }
    }

    public function commit($localCommit=true){

        if(isset($this->action) && $this->action == MessageAction::PREPARING){
            $this->prepare(true);
        }

        $now = Helpers::getNow();
        $data["action"] = MessageAction::SUBMITTED;
        $data["status"] = MessageStatus::WAITING;
        $data["available_at"] = Helpers::formartTime($now);
        $data["id"] = $this->id;

        $this->client->transCommit($data);
        if($localCommit){
            $this->client->localCommit();
        }
        try {
            $this->serverTransSubmit();
        }catch (\Exception $e){
            $this->client->logError("YiMQ.SERVER_SUBMIT: ". $e->getMessage(),["id"=>$this->id]);
        }


        return $data;
    }

    private function serverTransSubmit(){
        $this->client->initGrpc();

        $request = new TransActionRequest();
        $request->setMessageId($this->id);

        list($reply, $status) = $this->client->grpcClient->TransSubmit($request)->wait();
        if($status->code != 0){
           throw new SystemException("($status->code) $status->details");
        }
        return null;
    }

    public function rollback($localRollback=true){
        if($localRollback){
            $this->client->localRollback();
        }
        $now = Helpers::getNow();
        $data['action'] = MessageAction::CANCELED;
        $data["status"] = MessageStatus::WAITING;
        $data["available_at"] = Helpers::formartTime($now);
        $data["id"] = $this->id;
        $this->client->transRollBack($data);
        try {
            $this->serverTransCancel();
        }catch (\Exception $e){
            $this->client->logError("YiMQ.SERVER_SUBMIT: ". $e->getMessage(),["id"=>$this->id]);
        }
        return $data;
    }

    private function serverTransCancel(){
        $this->client->initGrpc();


        $request = new TransActionRequest();
        $request->setMessageId($this->id);

        list($reply, $status) = $this->client->grpcClient->TransCancel($request)->wait();
        if($status->code != 0){
            throw new SystemException("($status->code) $status->details");
        }
        return null;
    }

    public function tcc(string $processor):TransTcc{
        return new TransTcc($this,$processor);
    }

    public function ec(string $processor):TransEc{
        return new TransEc($this,$processor);
    }

    public function xa(string $processor):TransXa{
        return new TransXa($this,$processor);
    }

    public function saga(string $processor):TransSaga{
        return new TransSaga($this,$processor);
    }

    public function addPreparingChild(TransChildMessage $child){
        $this->preparingChildren[] = $child;
    }

//    public function relation_id(){
//        return $this->relation_id;
//    }
}