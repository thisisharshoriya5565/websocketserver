<?php

namespace Bhanu\WebSocketServer;

class WebSocketServer
{
    protected $clients = [];

    // public function run()
    // {
    //     $webSocket = stream_socket_server("tcp://0.0.0.0:8080", $errno1, $errstr1);
    //     $tcpServer = stream_socket_server("tcp://127.0.0.1:8090", $errno2, $errstr2);

    //     if (!$webSocket || !$tcpServer) {
    //         echo "Error: $errstr1 | $errstr2\n";
    //         exit(1);
    //     }

    //     echo "WebSocket server started on 0.0.0.0:8080\n";
    //     echo "TCP command server started on 127.0.0.1:8090\n";

    //     $this->clients = [];
    //     $this->clients[] = $webSocket;

    //     while (true) {
    //         $read = $this->clients;
    //         $read[] = $tcpServer;

    //         $write = $except = null;
    //         if (stream_select($read, $write, $except, null)) {
    //             foreach ($read as $socket) {
    //                 if ($socket === $tcpServer) {
    //                     // TCP command connection (from Laravel app)
    //                     $tcpClient = stream_socket_accept($tcpServer);
    //                     $message = fread($tcpClient, 1024);

    //                     echo "TCP Received: $message\n";

    //                     // Broadcast to all WS clients except the servers themselves
    //                     foreach ($this->clients as $client) {
    //                         if ($client !== $webSocket && $client !== $tcpServer) {
    //                             fwrite($client, $this->encodeWebSocketMessage($message));
    //                         }
    //                     }

    //                     fclose($tcpClient);
    //                 } elseif ($socket === $webSocket) {
    //                     // New WebSocket client connection
    //                     $client = stream_socket_accept($webSocket);
    //                     stream_set_blocking($client, 0); // non-blocking mode
    //                     $this->clients[] = $client;
    //                     echo "New WebSocket client connected\n";
    //                 } else {
    //                     // Read from existing WebSocket clients
    //                     $data = fread($socket, 1024);
    //                     if ($data === false || $data === '') {
    //                         echo "Client disconnected\n";
    //                         $this->removeClient($socket);
    //                     } else {
    //                         echo "Received from WS client: $data\n";
    //                         // You can add logic here if you want to handle messages from WS clients
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }

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

        $handshakes = [];

        while (true) {
            $read = $this->clients;
            $read[] = $tcpServer;

            $write = $except = null;
            if (stream_select($read, $write, $except, null)) {
                foreach ($read as $socket) {
                    if ($socket === $tcpServer) {
                        // TCP command (Laravel)
                        $tcpClient = stream_socket_accept($tcpServer);
                        $message = fread($tcpClient, 1024);
                        echo "TCP Received: $message\n";

                        foreach ($this->clients as $client) {
                            if ($client !== $webSocket && $client !== $tcpServer) {
                                fwrite($client, $this->encodeWebSocketMessage($message));
                            }
                        }

                        fclose($tcpClient);
                    } elseif ($socket === $webSocket) {
                        // New WebSocket connection
                        $client = stream_socket_accept($webSocket);
                        stream_set_blocking($client, 0);
                        $this->clients[] = $client;
                        echo "New WebSocket connection waiting for handshake\n";
                    } else {
                        // Read from existing WebSocket client
                        $data = fread($socket, 1024);
                        if ($data === false || $data === '') {
                            $this->removeClient($socket);
                            echo "Client disconnected\n";
                            continue;
                        }

                        if (!isset($handshakes[(int)$socket])) {
                            // Perform handshake
                            if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $data, $matches)) {
                                $key = trim($matches[1]);
                                $acceptKey = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
                                $headers = "HTTP/1.1 101 Switching Protocols\r\n"
                                    . "Upgrade: websocket\r\n"
                                    . "Connection: Upgrade\r\n"
                                    . "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
                                fwrite($socket, $headers);
                                $handshakes[(int)$socket] = true;
                                echo "Handshake completed with client\n";
                            }
                        } else {
                            echo "WS Data received (after handshake): $data\n";
                            // handle WS message if needed
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
