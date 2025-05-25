<?php

namespace Bhanu\WebSocketServer;

class WebSocketServer
{
    protected string $host;
    protected int $port;
    /** @var resource[] Array of client sockets keyed by (int)socket */
    protected array $clients = [];
    /** @var bool[] Tracks handshake completion for clients */
    protected array $handshakesDone = [];

    public function __construct(string $host = '0.0.0.0', int $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function start(): void
    {
        $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);

        if (!$server) {
            echo "Error: $errstr ($errno)\n";
            return;
        }

        stream_set_blocking($server, false);

        echo "WebSocket server started on {$this->host}:{$this->port}\n";

        // Add server socket to clients list (to listen for new connections)
        $this->clients[(int)$server] = $server;

        while (true) {
            $read = $this->clients;
            $write = $except = null;

            // Wait for activity on any socket, with 50ms timeout
            if (stream_select($read, $write, $except, 0, 50000) > 0) {
                foreach ($read as $socket) {
                    if ($socket === $server) {
                        // New connection request on server socket
                        $client = stream_socket_accept($server, 0);
                        if ($client) {
                            stream_set_blocking($client, false);
                            $clientId = (int)$client;
                            $this->clients[$clientId] = $client;
                            $this->handshakesDone[$clientId] = false;
                            echo "New client connected: {$clientId}\n";
                        }
                    } else {
                        $clientId = (int)$socket;
                        $data = fread($socket, 2048);

                        if ($data === false || $data === '') {
                            // Client disconnected or no data
                            $this->disconnectClient($socket);
                            continue;
                        }

                        if (!$this->handshakesDone[$clientId]) {
                            // Check if it's internal Laravel TCP message (e.g., starts with special prefix)
                            if (strpos($data, '__LARAVEL__') === 0) {
                                $message = substr($data, strlen('__LARAVEL__'));
                                echo "Broadcasting internal message: $message\n";
                                $this->broadcast($message, null);
                                $this->disconnectClient($socket);
                            } else {
                                $this->performHandshake($socket, $data);
                            }
                            // $this->performHandshake($socket, $data);
                        } else {
                            $message = $this->unmask($data);
                            echo "Received from client {$clientId}: $message\n";

                            // Broadcast the received message to all other clients
                            $this->broadcast($message, $socket);
                        }
                    }
                }
            }
        }

        fclose($server);
    }

    protected function performHandshake($client, string $headers): void
    {
        if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $matches)) {
            $this->disconnectClient($client);
            return;
        }

        $key = trim($matches[1]);
        $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $upgradeHeaders = implode("\r\n", [
            "HTTP/1.1 101 Switching Protocols",
            "Upgrade: websocket",
            "Connection: Upgrade",
            "Sec-WebSocket-Accept: $acceptKey",
            "\r\n"
        ]);

        fwrite($client, $upgradeHeaders);

        $clientId = (int)$client;
        $this->handshakesDone[$clientId] = true;

        echo "Handshake completed for client: {$clientId}\n";
    }

    protected function broadcast(string $message, $fromSocket): void
    {
        $data = $this->mask($message);
        foreach ($this->clients as $clientId => $clientSocket) {
            if ($clientSocket !== $fromSocket && $this->isHandshakeComplete($clientId)) {
                fwrite($clientSocket, $data);
            }
        }
    }


    protected function isHandshakeComplete(int $clientId): bool
    {
        return isset($this->handshakesDone[$clientId]) && $this->handshakesDone[$clientId];
    }



    protected function disconnectClient($socket): void
    {
        $clientId = (int)$socket;
        echo "Client {$clientId} disconnected\n";

        fclose($socket);
        unset($this->clients[$clientId], $this->handshakesDone[$clientId]);
    }

    protected function unmask(string $payload): string
    {
        $length = ord($payload[1]) & 127;

        if ($length === 126) {
            $masks = substr($payload, 4, 4);
            $data = substr($payload, 8);
        } elseif ($length === 127) {
            $masks = substr($payload, 10, 4);
            $data = substr($payload, 14);
        } else {
            $masks = substr($payload, 2, 4);
            $data = substr($payload, 6);
        }

        $unmasked = '';
        for ($i = 0, $len = strlen($data); $i < $len; ++$i) {
            $unmasked .= $data[$i] ^ $masks[$i % 4];
        }

        return $unmasked;
    }

    protected function mask(string $text): string
    {
        $b1 = chr(0x81); // FIN + text frame opcode
        $length = strlen($text);

        if ($length <= 125) {
            $b2 = chr($length);
        } elseif ($length <= 65535) {
            $b2 = chr(126) . pack('n', $length);
        } else {
            $b2 = chr(127) . pack('J', $length);
        }

        return $b1 . $b2 . $text;
    }
}
