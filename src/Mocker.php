<?php


namespace YiluTech\YiMQ;


use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Grpc\Services\TryRequest;

class Mocker
{
    protected array $mocks = [];
    public function tcc(string|array $processor,string|array|callable $result,string $error=null){
        $this->mocks[] = ["type"=>MessageType::TRANS_TCC,"processor"=>$processor,"result"=>$result,"error"=>$error];
    }
    public function xa(string|array $processor,string|array|callable $result,string $error=null){
        $this->mocks[] = ["type"=>MessageType::TRANS_XA,"processor"=>$processor,"result"=>$result,"error"=>$error];
    }
    public function saga(string|array $processor,string|array|callable $result,string $error=null){
        $this->mocks[] = ["type"=>MessageType::TRANS_SAGA,"processor"=>$processor,"result"=>$result,"error"=>$error];
    }

    public function match(TryRequest $tryRequest){
        foreach ($this->mocks as $index => $mock){
            $processor = "{$tryRequest->getConsumer()}@{$tryRequest->getProcessor()}";
            if($mock['type'] == $tryRequest->getType() &&  $mock['processor'] == $processor){
                array_splice($this->mocks,$index,1);
                return [$mock['result'], $mock['error']];
            }
        }
        return null;
    }
}