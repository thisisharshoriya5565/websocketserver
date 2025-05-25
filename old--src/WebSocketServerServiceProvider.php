<?php

namespace Bhanu\WebSocketServer;

use Illuminate\Support\ServiceProvider;
use Bhanu\WebSocketServer\Console\WebSocketServerCommand;

class WebSocketServerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            WebSocketServerCommand::class,
        ]);
    }

    public function boot()
    {
        if (php_sapi_name() === 'cli') {
            return; // don't run in artisan commands
        }

        exec('php ' . base_path('artisan') . ' websocket:serve > /dev/null 2>&1 &');
    }
}
