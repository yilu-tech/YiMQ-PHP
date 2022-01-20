<?php
namespace YiluTech\YiMQ\Processors;

use YiluTech\YiMQ\Clients\Client;
use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Helpers;

abstract class Processor
{
    protected Client $client;
    protected string $type;
    protected string $id;
    protected string $trans_id;
    protected string $producer;
    protected string $processor;
    private string|array $data;
    protected string $action;
    protected string $trans_topic;

    protected array $context;

    private $validator;
    public function __construct($client,$processor)
    {
        $this->client = $client;
        $this->processor = $processor;
    }

    protected function init($context){
        if($context['type'] != $this->type){
            throw new SystemException("{$this->processor} processor does not support process {$context['type']}.");
        }
        $this->id = $context['id'];
        $this->trans_id = $context['relation_id'];
        $this->producer = $context['producer'];
        $this->processor = $context['processor'];
//        $this->data = $context['data']; //TODO

        if(!empty($this->trans_topic)){
            $this->child_trans_id = Helpers::ksuid();
        }
    }

    protected function createProcess(){
        $now = Helpers::getNow();
        $data = [
            'id' => $this->id,
            'producer'=>$this->producer,
            'trans_id'=>$this->trans_id,
            'type' => $this->type,
            'processor'=> $this->processor,
//            'data'=>json_encode($this->data), //TODO
            'data'=>json_encode([]),
            'action'=>MessageAction::PREPARING,
            'created_at' => Helpers::formartTime($now),
            'updated_at'=>Helpers::formartTime($now),
        ];
        $this->client->createProcess($data);
    }


    protected function loadAndLockProcessRecord(){
        try {
            $processRecord = $this->client->loadProcess($this->id,true);
            if(!$processRecord){
                return null;
            }

            $this->action = $processRecord['action'];
            return $processRecord;
        } catch (\Exception $e){
            throw new SystemException("lock: {$e->getMessage()}");
        }
    }
    protected function updateProcessRecordAction(string $action){
        $this->client->setProcessAction($this->id,$action);
    }

    public function childTransactionBegin(){
        if(isset($this->trans_topic)){
            $this->client->childTransaction($this->trans_topic,$this->id)->begin(false);
        }
    }
    public function childTransactionPrepare(){
        if(isset($this->trans_topic)){
            $this->client->prepare();
        }
    }

    public function childTransactionCommit(){
        if(isset($this->trans_topic)){
            $this->client->commit(false);
        }
    }
    public function childTransactionRollback(){
        if(isset($this->trans_topic)){
            $this->client->rollback(false);
        }
    }

    public function childTransactionRestoreAndCommit(){
        if(isset($this->trans_topic)){
            $this->client->restoreTransaction($this->id);
            $this->client->commit(false);
        }
    }
    public function childTransactionRestoreAndRollback(){
        if(isset($this->trans_topic)){
            $this->client->restoreTransaction($this->id);
            $this->client->rollback(false);
        }
    }

    abstract public function process($context);

    protected function runValidate(){
        $this->validate(function ($rules){
//            $this->validator = \Validator::make($this->data, $rules);
            $this->validator = \Validator::make([], $rules); //TODO
            return $this->validator;
        });
        $this->validator->validate();
    }
    protected abstract function validate($validator);

    protected function data(){
        return $this->data;
    }

}