<?php


namespace YiluTech\YiMQ\Tests\Processors;




use YiluTech\YiMQ\Processors\TransEcProcessor;

class UserCreateEcProcessor extends TransEcProcessor
{
    function submit()
    {

        $username = $this->data()["username"];

        if($username == 'error_test'){
            throw new \Exception($username);
        }

        $client = $this->client->getPdoClient();
        $sql = "INSERT INTO `users` (`username`, `status`) VALUES (?, ?)";
        $client->insert($sql,[$username,"active"]);
        return ['username'=>$username];
    }

    protected function validate($validator)
    {
        $validator([]);
    }
}