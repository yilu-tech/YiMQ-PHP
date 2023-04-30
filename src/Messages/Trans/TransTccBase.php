<?php
namespace YiluTech\YiMQ\Messages\Trans;


use Tuupola\Ksuid;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Grpc\Server\ChildMessage;
use YiluTech\YiMQ\Grpc\Server\TransChildPrepareReply;
use YiluTech\YiMQ\Grpc\Server\TransChildPrepareRequest;
use YiluTech\YiMQ\Grpc\Services\TryRequest;
use function PHPUnit\Framework\isEmpty;

class TransTccBase extends TransChildMessage
{
    protected string $type;

    protected string $prepare_error; //prepare error
    protected null|string|array $prepare_data; //prepare result



    protected function prepare(){
        $childMessage = new ChildMessage();
        $childMessage->setType($this->type);
        $childMessage->setConsumer($this->consumer);
        $childMessage->setProcessor($this->processor);

        if(gettype($this->data) == "array"){
            $childMessage->setData( json_encode($this->data));
        }else{
            $childMessage->setData($this->data);
        }

        $transChildPrepareRequest = new TransChildPrepareRequest();
        $transChildPrepareRequest->setMessageId($this->trans->id);
        $transChildPrepareRequest->setChild($childMessage);


        $this->ifDataEmptyThrow();



        if(isset($this->trans->client->mocker) && list($data,$error) = $this->trans->client->mocker->match($this)){
            $this->prepare_data = $data;
            if(isset($error)){
                $this->prepare_error = $error;
            }
            return $this;
        }

        $this->trans->client->initGrpc();

        /* @var $tryReply TransChildPrepareReply */
        list($tryReply, $status) = $this->trans->client->grpcClient->TransChildPrepare($transChildPrepareRequest)->wait();
        if($status->code != 0){
            throw new SystemException("code: {$status->code}, client try {$status->details}");
        }
        $this->prepare_data = json_decode($tryReply->getData(),true);
        $this->prepare_data = is_null($this->prepare_data) ?  $tryReply->getData() : $this->prepare_data;

        $this->prepare_error = $tryReply->getError();

        return $this;
    }

    public function getPrepareData()
    {
        return $this->prepare_data;
    }

}