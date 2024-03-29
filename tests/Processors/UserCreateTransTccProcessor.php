<?php


namespace YiluTech\YiMQ\Tests\Processors;


use Illuminate\Support\Facades\Log;
use YiluTech\YiMQ\Facades\YiMQ;
use YiluTech\YiMQ\Processors\TransTccProcessor;

class UserCreateTransTccProcessor extends TransTccProcessor
{

    protected function validate($validator)
    {
        $validator([]);
    }

    function try()
    {
//        $username = $this->data()['username'];
        Log::info("username",$this->data());
        return ["username"=>"jack-rename"];
    }

    function confirm()
    {

        // TODO: Implement confirm() method.
    }

    function cancel()
    {
        // TODO: Implement cancel() method.
    }
}