<?php


namespace YiluTech\YiMQ\Laravel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use YiluTech\YiMQ\ClientManager;
use YiluTech\YiMQ\Exceptions\SystemException;

class Controller extends BaseController
{
    public function __construct(ClientManager $clientManager)
    {
        $this->middleware(function($request, $next)use($clientManager){
            if (!$request->hasHeader("actor")){
                throw new SystemException("Authentication information does not exist");
            }

            $client = $clientManager->client($request->header('actor'));

            $request->client = $client;
            return $next($request);
        });
    }

    public function process(Request $request,ClientManager $clientManager){

        $client = $clientManager->client($request->header('actor'));
        $result =  $client->processorManager()->process($request->input());
        return $result;
    }

    public function transMessageCheck(Request $request,ClientManager $clientManager){
        $client = $clientManager->client($request->header('actor'));

        return $client->transCheck($request->input());
    }

    public function clear(){

    }

}