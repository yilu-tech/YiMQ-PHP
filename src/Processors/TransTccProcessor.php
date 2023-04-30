<?php
namespace YiluTech\YiMQ\Processors;

use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Constants\MessageProcessAction;
use YiluTech\YiMQ\Constants\MessageType;
use YiluTech\YiMQ\Exceptions\SystemException;

abstract class TransTccProcessor extends Processor
{
    protected string $type = MessageType::TRANS_TCC;
    public function process($context)
    {
        $this->init($context);

        $action = $context['action'];
        switch ($action){
            case MessageProcessAction::PREPARE:
                return $this->runTry();
            case MessageProcessAction::SUBMIT:
                return $this->runConfirm();
            case MessageProcessAction::CANCEL:
                return $this->runCancel();
            default:
                throw new SystemException("tcc processor does not support $action action.");
        }
    }

    protected function runTry(){
        $this->createProcess();
        $this->runValidate();
        $this->childTransactionBegin();
        $this->client->localBegin();

        try {

            $this->loadAndLockProcessRecord();
            $data = $this->try();
            $this->updateProcessRecordAction(MessageAction::PREPARED);

            $this->childTransactionPrepare();
            $this->client->localCommit();
            return [
                "message"=> "prepare_succeed",
                "data" => $data
            ];
        }catch (\Exception $e){
            $this->client->localRollback();
            $this->childTransactionRollback();
            throw $e;
        }
    }
    protected function runConfirm(){

        try {
            $this->client->localBegin();

            $this->loadAndLockProcessRecord();

            if($this->action == MessageAction::SUBMITTED){
                $this->client->localRollback();
                return ['message'=>"compensate_submit_succeed"];
            }

            if($this->action != MessageAction::PREPARED){
                $this->client->localRollback();
                throw new SystemException("status is {$this->action}");
            }
            $this->confirm();

            $this->updateProcessRecordAction(MessageAction::SUBMITTED);

            $this->childTransactionRestoreAndCommit();
            $this->client->localCommit();

            return ['message'=>"submit_succeed"];
        }catch (\Exception $e){
            $this->client->localRollback();

            throw $e;
        }


    }
    protected function runCancel(){
        try {
            $this->client->localBegin();
            $is_locked = $this->loadAndLockProcessRecord();

            if(!$is_locked){
                $this->client->localRollback();
                return ['message'=>"not_prepare"];
            }

            if($this->action == MessageAction::CANCELED){
                $this->client->localRollback();
                return ['message'=>"compensate_cancel_succeed"];
            }

            if($this->action == MessageAction::PREPARING){
                $this->updateProcessRecordAction(MessageAction::CANCELED);
                $this->client->localCommit();
                return ['message'=>"preparing_cancel_succeed"];
            }

            if($this->action != MessageAction::PREPARED){
                $this->client->localRollback();
                throw new SystemException("status is {$this->action}");
            }

            $result = $this->cancel();

            $this->updateProcessRecordAction(MessageAction::CANCELED);
            $this->childTransactionRestoreAndRollback();
            $this->client->localCommit();

            return ['message'=>"cancel_succeed"];
        }catch (\Exception $e){
            $this->client->localRollback();

            throw $e;
        }

    }
    abstract function try();
    abstract function confirm();
    abstract function cancel();
}