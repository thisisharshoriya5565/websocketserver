<?php

namespace Bhanu\WebSocketServer;

class WebSocketServer
{
    protected $clients = [];

    public function run()
    {
        $webSocket = stream_socket_server("tcp://0.0.0.0:8080", $errno1, $errstr1);
        $tcpServer = stream_socket_server("tcp://127.0.0.1:8090", $errno2, $errstr2);

        if (!$webSocket || !$tcpServer) {
            echo "Error: $errstr1 | $errstr2\n";
            exit(1);
        }

        echo "WebSocket server started on 0.0.0.0:8080\n";
        echo "TCP command server started on 127.0.0.1:8090\n";

        $this->clients = [];
        $this->clients[] = $webSocket;

        while (true) {
            $read = $this->clients;
            $read[] = $tcpServer;

            $write = $except = null;
            if (stream_select($read, $write, $except, null)) {
                foreach ($read as $socket) {
                    if ($socket === $tcpServer) {
                        // TCP command connection (from Laravel app)
                        $tcpClient = stream_socket_accept($tcpServer);
                        $message = fread($tcpClient, 1024);

                        echo "TCP Received: $message\n";

                        // Broadcast to all WS clients except the servers themselves
                        foreach ($this->clients as $client) {
                            if ($client !== $webSocket && $client !== $tcpServer) {
                                fwrite($client, $this->encodeWebSocketMessage($message));
                            }
                        }

                        fclose($tcpClient);
                    } elseif ($socket === $webSocket) {
                        // New WebSocket client connection
                        $client = stream_socket_accept($webSocket);
                        stream_set_blocking($client, 0); // non-blocking mode
                        $this->clients[] = $client;
                        echo "New WebSocket client connected\n";
                    } else {
                        // Read from existing WebSocket clients
                        $data = fread($socket, 1024);
                        if ($data === false || $data === '') {
                            echo "Client disconnected\n";
                            $this->removeClient($socket);
                        } else {
                            echo "Received from WS client: $data\n";
                            // You can add logic here if you want to handle messages from WS clients
                        }
                    }
                }
            }
        }
    }

    protected function removeClient($socket)
    {
        $index = array_search($socket, $this->clients);
        if ($index !== false) {
            fclose($socket);
            unset($this->clients[$index]);
            // reindex array
            $this->clients = array_values($this->clients);
        }
    }

    // Encode message to WebSocket frame (text frame)
    protected function encodeWebSocketMessage(string $payload): string
    {
        $frameHead = chr(129); // FIN + text frame opcode

        $length = strlen($payload);
        if ($length <= 125) {
            $frameHead .= chr($length);
        } elseif ($length <= 65535) {
            $frameHead .= chr(126) . pack('n', $length);
        } else {
            $frameHead .= chr(127) . pack('J', $length);
        }

        return $frameHead . $payload;
    }
}
