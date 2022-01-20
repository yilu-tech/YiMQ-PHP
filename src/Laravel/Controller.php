<?php


namespace YiluTech\YiMQ\Laravel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use YiluTech\YiMQ\ClientManager;

class Controller extends BaseController
{
    public function process(ClientManager $clientManager){
        Log::info('test',Request::all());
        return $clientManager->client()->processorManager()->process(Request::all());
    }

}