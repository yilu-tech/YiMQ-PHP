<?php


namespace YiluTech\YiMQ;


use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Grpc\Services\TryRequest;
use YiluTech\YiMQ\Messages\Trans\TransChildMessage;

class Mocker
{
    protected array $mocks = [];
    protected array $transactionMocks = [];
    public function tcc(string|array $processor,string|array|callable $result,string $error=null){
        $this->mocks[] = ["type"=>MessageType::TRANS_TCC,"processor"=>$processor,"data"=>$result,"error"=>$error];
    }
    public function xa(string|array $processor,string|array|callable $result,string $error=null){
        $this->mocks[] = ["type"=>MessageType::TRANS_XA,"processor"=>$processor,"data"=>$result,"error"=>$error];
    }
    public function saga(string|array $processor,string|array|callable $result,string $error=null){
        $this->mocks[] = ["type"=>MessageType::TRANS_SAGA,"processor"=>$processor,"data"=>$result,"error"=>$error];
    }

    public function match(TransChildMessage $transChild){
        foreach ($this->mocks as $index => $mock){
            $processor = "{$transChild->getConsumer()}@{$transChild->getProcessor()}";
            if($mock['type'] == $transChild->getType() &&  $mock['processor'] == $processor){
                array_splice($this->mocks,$index,1);
                return [$mock['data'], $mock['error']];
            }
        }
        return null;
    }

    public function transaction(string $topic){
        $this->transactionMocks[] = ["topic"=>$topic,"action"=>MessageAction::PREPARING];
    }
    public function prepare(string $topic){
        $this->transactionMocks[] = ["topic"=>$topic,"action"=>MessageAction::PREPARED];
    }

    public function matchTransaction($topic,$action){

        foreach ($this->transactionMocks as $index => $mock){
            if($mock["topic"] == $topic && $mock["action"] == $action){
                array_splice($this->transactionMocks,$index,1);
                $str = md5(time());
                $token = substr($str,2,8);
                return $token;
            }
        }
        return null;
    }
}