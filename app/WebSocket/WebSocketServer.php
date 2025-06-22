<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        Log::info("New WebSocket connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        $type = $data['type'] ?? '';
        
        switch ($type) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'subscribe_device':
                $this->handleDeviceSubscription($from, $data);
                break;
            case 'unsubscribe_device':
                $this->handleDeviceUnsubscription($from, $data);
                break;
            default:
                Log::warning("Unknown message type: {$type}");
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        // Remove from user connections
        foreach ($this->userConnections as $userId => $connections) {
            $this->userConnections[$userId] = array_filter($connections, function($connection) use ($conn) {
                return $connection !== $conn;
            });
        }
        
        Log::info("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    protected function handleAuth($conn, $data)
    {
        $token = $data['token'] ?? '';
        
        // In a real application, you would validate the token
        // For now, we'll use a simple user ID
        $userId = $data['user_id'] ?? null;
        
        if ($userId) {
            if (!isset($this->userConnections[$userId])) {
                $this->userConnections[$userId] = [];
            }
            $this->userConnections[$userId][] = $conn;
            
            $conn->send(json_encode([
                'type' => 'auth_success',
                'message' => 'Authentication successful'
            ]));
            
            Log::info("User {$userId} authenticated on connection {$conn->resourceId}");
        } else {
            $conn->send(json_encode([
                'type' => 'auth_error',
                'message' => 'Authentication failed'
            ]));
        }
    }

    protected function handleDeviceSubscription($conn, $data)
    {
        $deviceId = $data['device_id'] ?? null;
        
        if ($deviceId) {
            // Store device subscription
            $conn->deviceSubscriptions = $conn->deviceSubscriptions ?? [];
            $conn->deviceSubscriptions[] = $deviceId;
            
            $conn->send(json_encode([
                'type' => 'subscription_success',
                'device_id' => $deviceId,
                'message' => 'Subscribed to device updates'
            ]));
        }
    }

    protected function handleDeviceUnsubscription($conn, $data)
    {
        $deviceId = $data['device_id'] ?? null;
        
        if ($deviceId && isset($conn->deviceSubscriptions)) {
            $conn->deviceSubscriptions = array_filter($conn->deviceSubscriptions, function($id) use ($deviceId) {
                return $id != $deviceId;
            });
            
            $conn->send(json_encode([
                'type' => 'unsubscription_success',
                'device_id' => $deviceId,
                'message' => 'Unsubscribed from device updates'
            ]));
        }
    }

    public function broadcastDeviceUpdate($deviceId, $data)
    {
        $message = json_encode([
            'type' => 'device_update',
            'device_id' => $deviceId,
            'data' => $data
        ]);

        foreach ($this->clients as $client) {
            if (isset($client->deviceSubscriptions) && in_array($deviceId, $client->deviceSubscriptions)) {
                $client->send($message);
            }
        }
    }

    public function broadcastToUser($userId, $data)
    {
        $message = json_encode($data);
        
        if (isset($this->userConnections[$userId])) {
            foreach ($this->userConnections[$userId] as $conn) {
                $conn->send($message);
            }
        }
    }

    public function broadcastAlert($alert)
    {
        $message = json_encode([
            'type' => 'alert',
            'data' => [
                'id' => $alert->id,
                'type' => $alert->type,
                'title' => $alert->title,
                'message' => $alert->message,
                'device_id' => $alert->device_id,
                'triggered_at' => $alert->triggered_at,
            ]
        ]);

        // Broadcast to the user who owns the alert
        $this->broadcastToUser($alert->user_id, [
            'type' => 'alert',
            'data' => $alert
        ]);
    }
} 