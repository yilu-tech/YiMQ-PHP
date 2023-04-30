<?php
namespace YiluTech\YiMQ\Exceptions;


class BusinessException extends \Exception
{
    public array $data;
    public function __construct($message = "",array $data = [])
    {
        $this->data = $data;
        parent::__construct($message);
    }
}