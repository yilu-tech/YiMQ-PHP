<?php
namespace YiluTech\YiMQ\Messages\Trans;

use YiluTech\YiMQ\Constants\MessageType;

class TransTcc extends TransXa
{
    protected string $type = MessageType::TRANS_TCC;


    public function try():string|array{
       return $this->prepareConsumer();
    }
    public function result():string|array{
        return $this->result;
    }
}