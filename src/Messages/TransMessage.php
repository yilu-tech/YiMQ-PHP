<?php
namespace YiluTech\YiMQ\Messages;

use YiluTech\YiMQ\Clients\Client;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Constants\MessageType;
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
        protected Client $client,
        protected string $topic,
        callable $callback = null
    )
    {
        $this->callback = $callback;
        $this->action = MessageAction::PREPARING;
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

        if(is_null($this->callback)){return;};

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
        $this->id = Helpers::ksuid();
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

    /**
     * tcc专用
     */
    public function prepare($commitPrepare = false){
        if (count($this->preparingChildren) > 0){
            $this->client->prepareChildren($this->preparingChildren);
        }
        if(!$commitPrepare){
            $data["action"] = MessageAction::PREPARED;
            $data["status"] = MessageStatus::DELAYED; //保持状态
            $data["available_at"] = null; //暂停超时检测
            $data["id"] = $this->id;
            $this->client->transCommit($data);
        }
    }
    public function commit($localCommit=true){

        if(isset($this->action) && $this->action == MessageAction::PREPARING){
            $this->prepare(true);
        }

        $now = Helpers::getNow();
        $data["action"] = MessageAction::SUBMITTING;
        $data["status"] = MessageStatus::WAITING;
        $data["available_at"] = Helpers::formartTime($now);
        $data["id"] = $this->id;

        $this->client->transCommit($data);
        if($localCommit){
            $this->client->localCommit();
        }

        return $data;
    }

    public function rollback($localRollback=true){
        if($localRollback){
            $this->client->localRollback();
        }
        $now = Helpers::getNow();
        $data['action'] = MessageAction::CANCELLING;
        $data["status"] = MessageStatus::WAITING;
        $data["available_at"] = Helpers::formartTime($now);
        $data["id"] = $this->id;
        $this->client->transRollBack($data);
        return $data;
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