<?php
namespace YiluTech\YiMQ;


use YiluTech\YiMQ\Clients\Client;
use YiluTech\YiMQ\Exceptions\BusinessException;
use YiluTech\YiMQ\Exceptions\SystemException;

class ClientProcessorManager
{
    protected Client $client;
    private array $processorsMap;

    protected $dontReport = [
        BusinessException::class,
    ];

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function init(array $processors):ClientProcessorManager{
        $this->processorsMap = $processors;
        return $this;
    }

    public function process($context){
        $processorName = $context['processor'];
        $action = $context['action'];
        if(!isset($this->processorsMap[$processorName])){
            throw new SystemException("$processorName processor not found.");
        }
        $processor = new $this->processorsMap[$processorName]($this->client,$processorName);

        try {
            return $processor->process($context);
        }catch (\Exception $e){
            $this->exceptionLogHandler($e);
            return $this->exceptionReplyHandler($e);
        }

    }

    private function exceptionLogHandler(\Exception $e){
        foreach ($this->dontReport as $class){
            if ($e instanceof $class){
                return;
            }
        }
        $this->client->logError($e);
    }

    private function exceptionReplyHandler(\Exception $e){
        $reply["error"] = $e->getMessage();

        if (isset($e->data)){
            $reply["data"] = $e->data;
        }

        $reply["stack"] =  sprintf("## %s(%s) \n%s",$e->getFile(),$e->getLine(),$e->getTraceAsString());

        return $reply;
    }



}