<?php
namespace YiluTech\YiMQ\Messages\Trans;

use YiluTech\YiMQ\Constants\MessageType;

class TransSaga extends TransTccBase
{
    protected string $type = MessageType::TRANS_SAGA;


    public function try(){
        return $this->prepare();
    }
    public function result():string|array{
        return $this->result;
    }
}