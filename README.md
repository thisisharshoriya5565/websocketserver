# 🚀 WebSocketServer Laravel Package

Easily broadcast TCP messages to WebSocket clients with zero dependency on external libraries.

---

## 📦 Installation Instructions

### Step 1: Add VCS Repository & Install Package

Choose one of the following:

```bash
# Add repo then require
composer config repositories.websocketserver vcs https://github.com/thisisharshoriya5565/websocketserver.git
composer require bhanu/websocketserver:dev-main

# OR install directly with inline repo declaration
composer require bhanu/websocketserver:dev-main --repository='{"type":"vcs","url":"https://github.com/thisisharshoriya5565/websocketserver.git"}'


Step 2: Register Service Provider (Only for Laravel < 5.5)
In your config/app.php, add:

```bash
Bhanu\WebSocketServer\WebSocketServerServiceProvider::class,

Step 3: Run the WebSocket Server
Start the server using the custom Artisan command:

```bash
php artisan websocket:serve

# You can optionally set a custom host and port:

```bash
php artisan websocket:serve 127.0.0.1 9090

🔗 WebSocket Default Info
WebSocket Server: ws://127.0.0.1:8080

TCP Broadcast Port: tcp://127.0.0.1:8090

Messages sent to port 8090 will be broadcast to all connected WebSocket clients.

---

Let me know if you also want:
- Auto-start logic when Laravel boots
- `.env` config support
- Publishing config files
- Helper methods for broadcasting from controllers

I'd be happy to help you improve the whole package for production-level use.
