<?php

// app/Services/WebSocketManager.php
namespace App\Services;

class WebSocketManager
{
    public static function send($message)
    {
        // tcp-server.php
        $server = stream_socket_server("tcp://127.0.0.1:8090", $errno, $errstr);

        if (!$server) {
            echo "Error: $errstr ($errno)\n";
            exit(1);
        }

        echo "TCP server running on 127.0.0.1:8090\n";

        while ($client = stream_socket_accept($server)) {
            $data = fread($client, 1024);
            echo "Received: $data\n";
            fwrite($client, "Message received\n");
            fclose($client);
        }
    }
}
