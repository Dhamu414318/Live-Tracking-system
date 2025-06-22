<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\WebSocketServer as GpsWebSocketServer;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:serve {--port=8080 : Port to run the WebSocket server on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the WebSocket server for real-time GPS tracking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $port = $this->option('port');
        
        $this->info("Starting WebSocket server on port {$port}...");
        $this->info("Press Ctrl+C to stop the server.");
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new GpsWebSocketServer()
                )
            ),
            $port
        );

        $this->info("WebSocket server is running on ws://localhost:{$port}");
        
        $server->run();
    }
} 