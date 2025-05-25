<?php

namespace Bhanu\WebSocketServer;

class WebSocketServer
{
    protected $host;
    protected $port;
    protected $clients = [];

    public function __construct($host = '0.0.0.0', $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function start()
    {
        $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);

        if (!$server) {
            echo "Error: $errstr ($errno)\n";
            return;
        }

        stream_set_blocking($server, false);

        echo "WebSocket server started on {$this->host}:{$this->port}\n";

        $this->clients[] = $server;

        while (true) {
            $read = $this->clients;
            $write = $except = null;

            if (stream_select($read, $write, $except, 0, 50000) > 0) {
                foreach ($read as $socket) {
                    if ($socket === $server) {
                        // New connection
                        $client = stream_socket_accept($server);
                        if ($client) {
                            stream_set_blocking($client, false);
                            $this->clients[] = $client;
                        }
                    } else {
                        $data = fread($socket, 2048);
                        if (!$data) {
                            $this->disconnect($socket);
                            continue;
                        }

                        if (!$this->isHandshakeDone($socket)) {
                            $this->doHandshake($socket, $data);
                        } else {
                            $message = $this->unmask($data);
                            echo "Received: $message\n";

                            // Echo message back
                            $response = $this->mask("Echo: $message");
                            fwrite($socket, $response);
                        }
                    }
                }
            }
        }

        fclose($server);
    }

    protected function doHandshake($client, $headers)
    {
        if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $matches)) {
            $this->disconnect($client);
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

        // Tag the client as "handshake done"
        stream_socket_get_name($client, true); // trigger internal stream hash
        $clientId = (int)$client;
        $this->clients[$clientId] = $client;
    }

    protected function isHandshakeDone($socket)
    {
        $clientId = (int)$socket;
        return isset($this->clients[$clientId]);
    }

    protected function disconnect($socket)
    {
        $clientId = (int)$socket;
        echo "Client $clientId disconnected\n";
        fclose($socket);
        unset($this->clients[$clientId]);
    }

    protected function unmask($payload)
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
        for ($i = 0; $i < strlen($data); ++$i) {
            $unmasked .= $data[$i] ^ $masks[$i % 4];
        }

        return $unmasked;
    }

    protected function mask($text)
    {
        $b1 = chr(0x81); // FIN + text frame opcode
        $length = strlen($text);
        if ($length <= 125) {
            $b2 = chr($length);
        } elseif ($length <= 65535) {
            $b2 = chr(126) . pack("n", $length);
        } else {
            $b2 = chr(127) . pack("J", $length);
        }

        return $b1 . $b2 . $text;
    }
}
