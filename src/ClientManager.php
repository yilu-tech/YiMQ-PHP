<?php


namespace YiluTech\YiMQ;



use YiluTech\YiMQ\Clients\Client;
use YiluTech\YiMQ\Exceptions\SystemException;

class ClientManager
{

    protected string $default = 'yimq';
    protected array $clients = [];

    public function __construct()
    {

    }


    public function client($name = null):Client
    {

        $name = $name ?: $this->default;
        if (! isset($this->clients[$name])) {
            throw new SystemException("yimq $name client not exists");
        }
        $client = $this->clients[$name];
        return $client;
    }

    public function add(string $name, Client $client){
        $this->clients[$name] = $client;
    }


    public function setDefault($clientName){
        $this->default = $clientName;
    }

//    public function __call($method, $parameters)
//    {
//        return $this->connection()->$method(...$parameters);
//    }



}