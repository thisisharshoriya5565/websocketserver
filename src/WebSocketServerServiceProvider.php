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
        //
    }
}
