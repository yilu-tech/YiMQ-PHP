<?php
namespace YiluTech\YiMQ\Exceptions;

use Throwable;

class SystemException extends \Exception
{
    public function __construct($message = "", Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}