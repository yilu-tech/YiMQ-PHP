<?php

namespace YiluTech\YiMQ\Tests\Laravel\App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        \DB::listen(function($query) {
//            \Log::info(
//                $query->sql
//            );
//        });
    }
}
