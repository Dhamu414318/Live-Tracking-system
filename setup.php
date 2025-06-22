<?php

echo "GPS Tracking System - Setup Script\n";
echo "==================================\n\n";

// Step 1: Check PHP version
echo "1. Checking PHP version...\n";
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "✓ PHP version " . PHP_VERSION . " is compatible\n";
} else {
    echo "✗ PHP version " . PHP_VERSION . " is too old. Required: 8.1.0+\n";
    exit(1);
}

// Step 2: Check required extensions
echo "\n2. Checking required PHP extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'tokenizer', 'xml', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ $ext extension is loaded\n";
    } else {
        echo "✗ $ext extension is missing\n";
    }
}

// Step 3: Check if .env file exists
echo "\n3. Checking configuration...\n";
if (file_exists('.env')) {
    echo "✓ .env file exists\n";
} else {
    echo "✗ .env file not found. Creating from example...\n";
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "✓ .env file created from .env.example\n";
    } else {
        echo "✗ .env.example not found. Please create .env file manually.\n";
    }
}

// Step 4: Check database connection
echo "\n4. Testing database connection...\n";
$db_configs = [
    ['host' => 'localhost', 'db' => 'gps_tracking', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'db' => 'gps_tracking', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'db' => 'my_tracking_backend', 'user' => 'root', 'pass' => ''],
];

$db_connected = false;
foreach ($db_configs as $config) {
    try {
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['db']}", $config['user'], $config['pass']);
        echo "✓ Database connection successful: {$config['host']}/{$config['db']}\n";
        $db_connected = true;
        break;
    } catch (PDOException $e) {
        echo "✗ Database connection failed: {$config['host']}/{$config['db']} - " . $e->getMessage() . "\n";
    }
}

if (!$db_connected) {
    echo "\nDatabase setup instructions:\n";
    echo "1. Create a MySQL database named 'gps_tracking'\n";
    echo "2. Update .env file with correct database credentials\n";
    echo "3. Run: php artisan migrate\n";
    echo "4. Run: php artisan db:seed\n";
}

// Step 5: Check if migrations have been run
if ($db_connected) {
    echo "\n5. Checking database tables...\n";
    try {
        $tables = ['users', 'devices', 'positions', 'geofences', 'alerts', 'trips'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '$table' exists\n";
            } else {
                echo "✗ Table '$table' missing - run migrations\n";
            }
        }
    } catch (PDOException $e) {
        echo "✗ Error checking tables: " . $e->getMessage() . "\n";
    }
}

// Step 6: Check if data has been seeded
if ($db_connected) {
    echo "\n6. Checking sample data...\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $user_count = $stmt->fetchColumn();
        echo "✓ Users table has $user_count records\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM devices");
        $device_count = $stmt->fetchColumn();
        echo "✓ Devices table has $device_count records\n";
        
        if ($user_count == 0) {
            echo "✗ No users found - run: php artisan db:seed\n";
        }
    } catch (PDOException $e) {
        echo "✗ Error checking data: " . $e->getMessage() . "\n";
    }
}

// Step 7: Check storage permissions
echo "\n7. Checking storage permissions...\n";
$storage_paths = ['storage', 'storage/logs', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views'];
foreach ($storage_paths as $path) {
    if (is_dir($path) && is_writable($path)) {
        echo "✓ $path is writable\n";
    } else {
        echo "✗ $path is not writable\n";
    }
}

// Step 8: Check if app key is set
echo "\n8. Checking application key...\n";
if (file_exists('.env')) {
    $env_content = file_get_contents('.env');
    if (strpos($env_content, 'APP_KEY=base64:') !== false) {
        echo "✓ Application key is set\n";
    } else {
        echo "✗ Application key not set - run: php artisan key:generate\n";
    }
}

echo "\nSetup Summary:\n";
echo "==============\n";
echo "If all checks passed, your GPS Tracking System is ready!\n\n";
echo "Next steps:\n";
echo "1. Start the web server: php artisan serve\n";
echo "2. Start WebSocket server: php artisan websocket:serve\n";
echo "3. Access the application at http://localhost:8000\n";
echo "4. Login with admin@example.com / password\n\n";
echo "For API testing, use the test_api.php script.\n";
echo "Happy tracking! 🚗📱\n"; 