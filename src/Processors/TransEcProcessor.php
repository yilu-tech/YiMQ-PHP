<?php
namespace YiluTech\YiMQ\Processors;

use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageProcessAction;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Exceptions\SystemException;

abstract class TransEcProcessor extends Processor
{
    protected string $type = MessageType::TRANS_EC;
    public function process($context)
    {
        $this->init($context);
        if($context['action'] != MessageProcessAction::SUBMIT){
            throw new SystemException("ec processor only supports COMMIT action.");
        }
        $process = $this->loadAndLockProcessRecord();

        if(!$process){
            $this->createProcess();
        }elseif ($process['action'] == MessageAction::SUBMITTED){
            return ['message'=>"retry_succeed"];
        }

        $this->runValidate();

        try {

            $this->childTransactionBegin();
            $this->client->localBegin();

            $this->loadAndLockProcessRecord();

            $data = $this->runSubmit();

            $this->updateProcessRecordAction(MessageAction::SUBMITTED);

            $this->childTransactionCommit();
            $this->client->localCommit();
            return ['message'=>"succeed",'data'=>$data];
        }catch (\Exception $e){
            $this->client->localRollback();
            $this->childTransactionRollback();
            throw $e;
        }
    }



    private function runSubmit(){
        return $this->submit();
    }

    abstract function submit();
}