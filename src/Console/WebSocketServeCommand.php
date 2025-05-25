<?php

namespace Bhanu\WebSocketServer\Console;

use Illuminate\Console\Command;
use Bhanu\WebSocketServer\WebSocketServer;

class WebSocketServeCommand extends Command
{
    protected $signature = 'websocket:serve';
    protected $description = 'Start the WebSocket server';

    public function handle()
    {
        $this->info('Starting WebSocket server...');
        $server = new WebSocketServer();
        $server->run();
    }
}
