<?php
namespace YiluTech\YiMQ\Messages\Trans;

use Tuupola\Ksuid;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageStatus;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Grpc\Server\ChildMessage;
use YiluTech\YiMQ\Grpc\Server\MessageInfo;
use YiluTech\YiMQ\Grpc\Server\MessageOptions;
use YiluTech\YiMQ\Helpers;
use YiluTech\YiMQ\Messages\TransMessage;

class TransEc extends TransChildMessage
{
    public string $type = MessageType::TRANS_EC;

    public function join(){
        $this->trans->addPreparingChild($this);
    }

    public function getPrepareDataOld()
    {
        $ksuid = new Ksuid();

        $now = Helpers::getNow();
        return [
            "id" => $ksuid->string(),
            "relation_id" => $this->trans->id,
            "type" => $this->type,
            "topic" => null,
            "action" => MessageAction::PREPARED ,
            "status" => MessageStatus::DELAYED,
            "delay"=> null,
            "consumer"=> $this->consumer,
            "processor"=> $this->processor,
            "total" => null,
            "attempts" => 3,
            "created_at" => Helpers::formartTime($now),
            "results"=> '[]'
        ];
    }

    public function getPrepareData():ChildMessage
    {
        $childMessage = new ChildMessage();
        $childMessage->setConsumer($this->consumer);
        $childMessage->setType($this->type);
        $childMessage->setProcessor($this->processor);
        if(isset($this->data)){
            $childMessage->setData(json_encode($this->data));
        }

        return $childMessage;
    }
}