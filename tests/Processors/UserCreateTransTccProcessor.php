<?php


namespace YiluTech\YiMQ\Tests\Processors;


use YiluTech\YiMQ\Facades\YiMQ;
use YiluTech\YiMQ\Processors\TransTccProcessor;

class UserCreateTransTccProcessor extends TransTccProcessor
{
    protected string $trans_topic = 'trans_test';

    protected function validate($validator)
    {
        $validator([]);
    }

    function try()
    {
        $username = $this->data()['username'];
        if($username == 'error_test'){
            throw new \Exception($username);
        }
        $this->client->ec("user@create")->data([])->join();
        return 'success';
    }

    function confirm()
    {
        dump(123);
        // TODO: Implement confirm() method.
    }

    function cancel()
    {
        // TODO: Implement cancel() method.
    }
}