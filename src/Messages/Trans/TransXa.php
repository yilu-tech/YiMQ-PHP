<?php
namespace YiluTech\YiMQ\Messages\Trans;


use Tuupola\Ksuid;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Grpc\Services\TryRequest;

class TransXa extends TransChildMessage
{
    protected string $type = MessageType::TRANS_XA;

    protected string $error; //prepare error
    protected string|array $result; //prepare result

    public function prepare():string|array{
        return $this->prepareConsumer();
    }

    protected function prepareConsumer():string|array{
        $ksuid = new Ksuid();

        $tryReq = new TryRequest();
        $tryReq->setId($ksuid->string());
        $tryReq->setTransId($this->trans->id);
        $tryReq->setType($this->type);
        $tryReq->setConsumer($this->consumer);
        $tryReq->setProcessor($this->processor);

        $this->ifDataEmptyThrow();


        if(gettype($this->data) == "array"){
            $tryReq->setData( json_encode($this->data));
        }else{
            $tryReq->setData($this->data);
        }




        list($result,$error) = $this->trans->client()->prepareToServer($tryReq);

        $this->result = $result;

        if(!empty($error)){
            $this->error = $error;
            throw new SystemException("consumer error: {$this->error}");
        }

        return $this->result;
    }
    public function result():string|array{
        return $this->result;
    }
}