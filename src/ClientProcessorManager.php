<?php
namespace YiluTech\YiMQ;


use YiluTech\YiMQ\Clients\Client;
use YiluTech\YiMQ\Exceptions\SystemException;

class ClientProcessorManager
{
    protected Client $client;
    private array $processorsMap;

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
        return $processor->process($context);
    }

}