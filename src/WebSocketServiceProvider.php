<?php

namespace Bhanu\WebSocketServer;

use Illuminate\Support\ServiceProvider;

class WebSocketServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register bindings if needed
    }

    // public function boot()
    // {
    //     // Publish config or routes if needed
    // }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Bhanu\WebSocketServer\Console\WebSocketServeCommand::class,
            ]);
        }
    }


    /***
     * Artisan Command to run server
     * Create this in your package so you can run the server with:
     * CMD :: php artisan websocket:serve
     */
}
