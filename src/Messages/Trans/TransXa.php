<?php
namespace YiluTech\YiMQ\Messages\Trans;


use Tuupola\Ksuid;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Grpc\Server\ChildMessage;
use YiluTech\YiMQ\Grpc\Server\TransChildPrepareReply;
use YiluTech\YiMQ\Grpc\Server\TransChildPrepareRequest;
use YiluTech\YiMQ\Grpc\Services\TryRequest;

class TransXa extends TransTccBase
{
    protected string $type = MessageType::TRANS_XA;


    public function prepare(){
        return parent::prepare();
    }
}