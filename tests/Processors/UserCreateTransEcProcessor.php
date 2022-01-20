<?php


namespace YiluTech\YiMQ\Tests\Processors;



use YiluTech\YiMQ\Processors\TransEcProcessor;

class UserCreateTransEcProcessor extends TransEcProcessor
{
    protected string $trans_topic = "ec_child_trans";
    function submit()
    {
        return "success";
    }

    protected function validate($validator)
    {
        $validator([]);
    }
}