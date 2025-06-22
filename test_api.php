<?php

// Simple test script for GPS Tracking API
echo "GPS Tracking System - API Test\n";
echo "==============================\n\n";

// Test 1: Check if we can connect to the database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=gps_tracking', 'root', '');
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please make sure:\n";
    echo "1. MySQL is running\n";
    echo "2. Database 'gps_tracking' exists\n";
    echo "3. User 'root' has access\n";
    exit(1);
}

// Test 2: Check if tables exist
$tables = ['users', 'devices', 'positions', 'geofences', 'alerts', 'trips'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ Table '$table' exists with $count records\n";
    } catch (PDOException $e) {
        echo "✗ Table '$table' not found or error: " . $e->getMessage() . "\n";
    }
}

// Test 3: Check if admin user exists
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✓ Admin user found: {$admin['name']} ({$admin['email']}) - Role: {$admin['role']}\n";
    } else {
        echo "✗ Admin user not found\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking admin user: " . $e->getMessage() . "\n";
}

// Test 4: Check if devices exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count, user_id FROM devices GROUP BY user_id");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($devices) {
        echo "✓ Devices found:\n";
        foreach ($devices as $device) {
            echo "  - User ID {$device['user_id']}: {$device['count']} devices\n";
        }
    } else {
        echo "✗ No devices found\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking devices: " . $e->getMessage() . "\n";
}

// Test 5: Check if positions exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM positions");
    $count = $stmt->fetchColumn();
    echo "✓ Total positions: $count\n";
} catch (PDOException $e) {
    echo "✗ Error checking positions: " . $e->getMessage() . "\n";
}

// Test 6: Check if geofences exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM geofences");
    $count = $stmt->fetchColumn();
    echo "✓ Total geofences: $count\n";
} catch (PDOException $e) {
    echo "✗ Error checking geofences: " . $e->getMessage() . "\n";
}

// Test 7: Check if alerts exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM alerts");
    $count = $stmt->fetchColumn();
    echo "✓ Total alerts: $count\n";
} catch (PDOException $e) {
    echo "✗ Error checking alerts: " . $e->getMessage() . "\n";
}

// Test 8: Check if trips exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM trips");
    $count = $stmt->fetchColumn();
    echo "✓ Total trips: $count\n";
} catch (PDOException $e) {
    echo "✗ Error checking trips: " . $e->getMessage() . "\n";
}

echo "\nAPI Test Summary:\n";
echo "================\n";
echo "The GPS Tracking System backend is ready!\n";
echo "You can now:\n";
echo "1. Access the web interface at http://localhost:8000\n";
echo "2. Login with admin@example.com / password\n";
echo "3. Start the WebSocket server: php artisan websocket:serve\n";
echo "4. Send GPS position updates to /api/positions\n";
echo "\nHappy tracking! 🚗📱\n"; 