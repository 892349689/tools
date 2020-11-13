<?php


namespace Layman\Tools;


use Illuminate\Support\ServiceProvider;

class LaymanToolsProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            RedisCli::class
        ]);
        $this->loadMigrationsFrom([
            __DIR__ . 'RedisCli.php'
        ]);
    }
}
