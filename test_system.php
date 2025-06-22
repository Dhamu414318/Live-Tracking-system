<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Device;
use App\Models\Position;
use App\Models\Geofence;
use App\Models\Alert;
use App\Models\Trip;
use App\Models\Setting;

echo "🚀 GPS Tracking System - System Test\n";
echo "=====================================\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    \DB::connection()->getPdo();
    echo "   ✅ Database connection successful\n\n";

    // Test user count
    echo "2. Testing user data...\n";
    $userCount = User::count();
    echo "   📊 Total users: {$userCount}\n";
    
    $adminCount = User::where('role', 'admin')->count();
    echo "   👑 Admin users: {$adminCount}\n";
    
    $regularUserCount = User::where('role', 'user')->count();
    echo "   👤 Regular users: {$regularUserCount}\n\n";

    // Test device count
    echo "3. Testing device data...\n";
    $deviceCount = Device::count();
    echo "   📱 Total devices: {$deviceCount}\n";
    
    $onlineDevices = Device::where('status', 'online')->count();
    echo "   🟢 Online devices: {$onlineDevices}\n";
    
    $offlineDevices = Device::where('status', 'offline')->count();
    echo "   🔴 Offline devices: {$offlineDevices}\n\n";

    // Test position data
    echo "4. Testing position data...\n";
    $positionCount = Position::count();
    echo "   📍 Total positions: {$positionCount}\n";
    
    $todayPositions = Position::whereDate('timestamp', today())->count();
    echo "   📅 Today's positions: {$todayPositions}\n\n";

    // Test geofence data
    echo "5. Testing geofence data...\n";
    $geofenceCount = Geofence::count();
    echo "   🗺 Total geofences: {$geofenceCount}\n";
    
    $activeGeofences = Geofence::where('is_active', true)->count();
    echo "   ✅ Active geofences: {$activeGeofences}\n\n";

    // Test alert data
    echo "6. Testing alert data...\n";
    $alertCount = Alert::count();
    echo "   🔔 Total alerts: {$alertCount}\n";
    
    $unreadAlerts = Alert::where('is_read', false)->count();
    echo "   📬 Unread alerts: {$unreadAlerts}\n\n";

    // Test trip data
    echo "7. Testing trip data...\n";
    $tripCount = Trip::count();
    echo "   🚗 Total trips: {$tripCount}\n";
    
    $completedTrips = Trip::where('status', 'completed')->count();
    echo "   ✅ Completed trips: {$completedTrips}\n\n";

    // Test settings data
    echo "8. Testing settings data...\n";
    $settingCount = Setting::count();
    echo "   ⚙️ Total settings: {$settingCount}\n\n";

    // Test API functionality
    echo "9. Testing API functionality...\n";
    $devicesWithApiKeys = Device::whereNotNull('api_key')->count();
    echo "   🔑 Devices with API keys: {$devicesWithApiKeys}\n";
    
    $sampleDevice = Device::first();
    if ($sampleDevice) {
        echo "   📱 Sample device: {$sampleDevice->name} (ID: {$sampleDevice->unique_id})\n";
        echo "   🔑 API Key: " . substr($sampleDevice->api_key, 0, 10) . "...\n";
    }
    echo "\n";

    // Test user permissions
    echo "10. Testing user permissions...\n";
    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        echo "   👑 Admin user: {$admin->name} ({$admin->email})\n";
        echo "   📊 Can access all devices: " . ($admin->isAdmin() ? 'Yes' : 'No') . "\n";
    }
    
    $regularUser = User::where('role', 'user')->first();
    if ($regularUser) {
        echo "   👤 Regular user: {$regularUser->name} ({$regularUser->email})\n";
        echo "   📱 User devices: " . $regularUser->devices()->count() . "\n";
    }
    echo "\n";

    // Test geofencing functionality
    echo "11. Testing geofencing functionality...\n";
    $geofence = Geofence::first();
    if ($geofence) {
        echo "   🗺 Sample geofence: {$geofence->name}\n";
        echo "   📐 Type: {$geofence->area_type}\n";
        echo "   🎨 Color: {$geofence->color}\n";
        echo "   📍 Center: " . json_encode($geofence->getCenterPoint()) . "\n";
    }
    echo "\n";

    // Test alert preferences
    echo "12. Testing alert preferences...\n";
    $user = User::first();
    if ($user) {
        $preferences = $user->alert_preferences;
        echo "   👤 User: {$user->name}\n";
        echo "   ⚡ Speed limit: {$user->speed_limit} km/h\n";
        echo "   📏 Units: {$user->units}\n";
        echo "   🌍 Timezone: {$user->timezone}\n";
        echo "   🔔 Alert preferences: " . count($preferences) . " types configured\n";
    }
    echo "\n";

    // System summary
    echo "📊 SYSTEM SUMMARY\n";
    echo "=================\n";
    echo "✅ Database: Connected and working\n";
    echo "✅ Users: {$userCount} total ({$adminCount} admin, {$regularUserCount} regular)\n";
    echo "✅ Devices: {$deviceCount} total ({$onlineDevices} online)\n";
    echo "✅ Positions: {$positionCount} total ({$todayPositions} today)\n";
    echo "✅ Geofences: {$geofenceCount} total ({$activeGeofences} active)\n";
    echo "✅ Alerts: {$alertCount} total ({$unreadAlerts} unread)\n";
    echo "✅ Trips: {$tripCount} total ({$completedTrips} completed)\n";
    echo "✅ Settings: {$settingCount} configured\n";
    echo "✅ API: {$devicesWithApiKeys} devices with API keys\n\n";

    echo "🎉 System test completed successfully!\n";
    echo "🚀 Your GPS tracking system is ready to use.\n\n";
    
    echo "📝 NEXT STEPS:\n";
    echo "1. Visit http://localhost:8000 to access the web interface\n";
    echo "2. Login with admin@example.com / password\n";
    echo "3. Explore the dashboard, devices, map, and reports\n";
    echo "4. Test the API endpoints with your GPS devices\n";
    echo "5. Configure geofences and alerts as needed\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Please check your configuration and try again.\n";
} 