<?php
namespace  YiLuTech\YiMQ\Tests\Unit;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use YiluTech\YiMQ\Interfaces\ClientOptions;
use YiluTech\YiMQ\Laravel\ServiceProvider;
use \YiluTech\YiMQ\Laravel\YiMQFacade;
use Illuminate\Support\Facades\DB;

class DemoTest  extends TestCase
{

    protected function defineEnvironment($app)
    {
        $app['config']->set('yimq.default', 'yimq');
        $app['config']->set('yimq.connections.yimq', [
            'name' => 'user',
            'address' => 'localhost'
        ]);

    }
    protected function getPackageProviders($app)
    {
        dump("getPackageProviders");
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'YiMQ' => YiMQFacade::class,
        ];
    }

    function getUser(array $data){

    }
    function testDemo(){
//         \YiMQ::connection();
//        DB::beginTransaction();
//        $date = new \DateTime("2022-01-01 15:33:39.016");
//        dump($date);
////        (new \DateTime('now',new \DateTimeZone("Asia/Shanghai")))->format("Y-m-d H:i:s.v")
//        dump($date->add(date_interval_create_from_date_string('5 seconds')));


        $this->assertEquals(1,1);
    }

    function testDatabase(){
        \DB::delete('delete from users');
        DB::listen(function ($query) {
            // $query->sql
            // $query->bindings
            // $query->time
            dump($query->sql);
        });

        $result = DB::insertGetId("insert into users set username='2'");
       dd(DB::connection()->getPdo()->lastInsertId());
//        \DB::delete('delete from users');
//        \DB::beginTransaction();    //主事务
//        try{
//            \DB::beginTransaction(); //子事务
//            \DB::insert('insert into users set username=1');
////            \DB::rollBack();         //子事务回滚
//            \DB::insert('insert into users set username=2');
//            \DB::commit();
//        }catch (\Exception $e) {
//            \DB::rollBack();
//            echo $e->getMessage();exit;
//        }
        $this->assertEquals(1,1);


    }

}