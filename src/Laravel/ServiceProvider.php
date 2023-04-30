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
        $clientManager->setDefault($app['config']['yimq.default']);
        $this->initClients($app,$clientManager);

        $routeConfig = $app['config']['yimq.route'];

        Route::name($routeConfig['name'])->prefix($routeConfig['prefix'])->group(function (){
            Route::post("/process", [Controller::class,'process']);
            Route::post("/trans_check",[Controller::class,"transMessageCheck"]);
            Route::post("/clear",[Controller::class,"clear"]);
        });
    }

    private function initClients($app,$clientManager){
        $connOptions = $app['config']['yimq.actors'];
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
        $option['actor'] = $key;
        $client = new LaravelClient($key,$option['db_connection'],$option);
        $client->processorManager()->init($option['processors']);

        return $client;
    }

}