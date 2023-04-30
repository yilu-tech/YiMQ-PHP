<?php
namespace YiluTech\YiMQ\Messages\Trans;

use YiluTech\YiMQ\Constants\MessageType;

class TransTcc extends TransTccBase
{
    protected string $type = MessageType::TRANS_TCC;


    public function try():TransTcc{
       return parent::prepare();
    }
}