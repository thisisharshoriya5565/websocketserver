<?php

namespace Bhanu\WebSocketServer\Console;

use Illuminate\Console\Command;
use Bhanu\WebSocketServer\WebSocketServer;

class WebSocketServerCommand extends Command
{
    protected $signature = 'websocket:serve {host=0.0.0.0} {port=8080}';
    protected $description = 'Start the WebSocket server';

    public function handle()
    {
        $host = $this->argument('host');
        $port = $this->argument('port');

        $this->info("Starting WebSocket server on {$host}:{$port}");

        $server = new WebSocketServer($host, $port);
        $server->start();
    }
}
