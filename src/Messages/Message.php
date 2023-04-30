<?php
namespace YiluTech\YiMQ\Messages;

use YiluTech\YiMQ\Clients\Client;
use YiluTech\YiMQ\Constants\MessageType;

class Message
{
    protected Client $client;
    protected string $id;
    protected string $type;
    protected string|array $data;
    protected string $action;
    protected string $status;

    public function client():Client{
        return $this->client;
    }

    public function getId():string{
        return $this->id;
    }
}