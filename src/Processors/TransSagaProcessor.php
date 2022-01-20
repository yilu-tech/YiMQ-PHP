<?php
namespace YiluTech\YiMQ\Processors;

use YiluTech\YiMQ\Constants\MessageProcessAction;
use YiluTech\YiMQ\Exceptions\SystemException;

abstract class TransSagaProcessor extends Processor
{

    public function process($action, $context)
    {
        switch ($action){
            case MessageProcessAction::PREPARE:
                $this->submit();
                break;
            case MessageProcessAction::ROLLBACK:
                $this->revert();
                break;
            default:
                throw new SystemException("tcc processor does not support $action action.");
        }
    }

    abstract function submit();
    abstract function revert();
}