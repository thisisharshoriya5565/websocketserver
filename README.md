# WebSocketServer Laravel Package

## Installation Instructions

### Step 1: Add Repository & Require Package

Then run:
# composer require bhanu/websocketserver:dev-main
composer config repositories.websocketserver vcs https://github.com/thisisharshoriya5565/websocketserver.git
composer require bhanu/websocketserver:dev-main

Step 2: Register Service Provider (if Laravel < 5.5)
Add this line in config/app.php providers array:
Bhanu\WebSocketServer\WebSocketServerServiceProvider::class,

Step 3: Run the WebSocket Server
Start the server using Artisan command:
php artisan websocket:serve

Optionally specify host and port:
php artisan websocket:serve 127.0.0.1 9090

---

If you want, I can help you generate a full `README.md` file for your GitHub repo including this and extra info — just let me know!
