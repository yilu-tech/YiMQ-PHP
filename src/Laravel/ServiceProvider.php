<?php
namespace YiluTech\YiMQ\Laravel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use YiluTech\YiMQ\ClientManager;
use YiluTech\YiMQ\Facades\YiMQ;


class ServiceProvider extends BaseServiceProvider
{


    public function register()
    {
        YiMQ::init();
        $this->app->instance(ClientManager::class,YiMQ::manager());

    }

    public function boot(Application $app, ClientManager $clientManager){
        $this->initClients($app,$clientManager);
    }

    private function initClients($app,$clientManager){
        $connOptions = $app['config']['yimq.connections'];
        foreach ($connOptions as $key => $option){
            $driver = $app['config']['database.connections'][$option["db_connection"]]['driver'];
            if($driver == 'mysql'){
                $client = $this->initMysqlClient($key,$option);
                $clientManager->add($key,$client);
            }else{
                throw new \Exception("unsupport $driver");
            }
        }
    }

    private function initMysqlClient($key,$option){
        $client = new LaravelClient($key,$option['db_connection'],[
            'address'=>$option['address'],
            'message_table'=>$option['tables']['message'],
            'process_table'=>$option['tables']['process']
        ]);
        $client->processorManager()->init($option['processors']);

        Route::name($option['route']['name'])->group(function () use ($option){
            Route::post($option['route']['path'], [Controller::class,'process']);
        });
        return $client;
    }

}