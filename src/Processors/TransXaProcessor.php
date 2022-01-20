<?php
namespace YiluTech\YiMQ\Processors;

use YiluTech\YiMQ\Constants\MessageProcessAction;
use YiluTech\YiMQ\Exceptions\SystemException;

abstract class TransXaProcessor extends Processor
{

    public function process($action, $context)
    {
        switch ($action){
            case MessageProcessAction::PREPARE:
                $this->runPrepare();
                break;
            case MessageProcessAction::COMMIT:
                $this->runCommit();
                break;
            case MessageProcessAction::ROLLBACK:
                $this->runRollback();
                break;
            default:
                throw new SystemException("tcc processor does not support $action action.");
        }
    }

    protected function runPrepare(){

    }
    protected function runCommit(){

    }
    protected function runRollback(){

    }
    abstract function prepare();
    abstract function commit();
    abstract function rollback();
}