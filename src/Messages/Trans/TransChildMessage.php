<?php

namespace YiluTech\YiMQ\Messages\Trans;

use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Messages\Message;
use YiluTech\YiMQ\Messages\TransMessage;

abstract class TransChildMessage extends Message
{
    protected TransMessage $trans;
    protected string $consumer;
    protected string $processor;
    protected string|array $data;
    public function __construct(TransMessage $trans,string $processor)
    {
        $this->trans = $trans;
        $this->processor = $processor;
        list($this->consumer,$this->processor) = explode("@",$processor);
    }

    public function data($data){
        $this->data = $data;
        return $this;
    }
    protected function ifDataEmptyThrow(){
        if(empty($this->data)){
            throw new SystemException("data has no value.");
        }
    }
    public function getPrepareData(){
    }
}