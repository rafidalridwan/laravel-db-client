<?php

namespace Rafid\DbClient;

use Illuminate\Support\ServiceProvider;

class DbClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dbclient');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/dbclient'),
            __DIR__ . '/../config/dbclient.php' => config_path('dbclient.php'),
            __DIR__ . '/../resources/css' => public_path('vendor/dbclient/css'),
            __DIR__ . '/../resources/js' => public_path('vendor/dbclient/js'),
        ], 'dbclient');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dbclient.php',
            'dbclient'
        );
    }
}
