<?php

require_once 'vendor/autoload.php';

use App\Models\Geofence;
use App\Models\Alert;
use App\Models\Device;

echo "Testing Geofence Relationships...\n";

try {
    // Test 1: Basic geofence loading
    echo "1. Loading geofence...\n";
    $geofence = Geofence::first();
    if ($geofence) {
        echo "   Found geofence: {$geofence->name}\n";
    } else {
        echo "   No geofences found\n";
        exit;
    }

    // Test 2: Devices relationship
    echo "2. Testing devices relationship...\n";
    $devices = $geofence->devices;
    echo "   Devices count: " . $devices->count() . "\n";

    // Test 3: Alerts relationship
    echo "3. Testing alerts relationship...\n";
    $alerts = $geofence->alerts;
    echo "   Alerts count: " . $alerts->count() . "\n";

    // Test 4: Eager loading
    echo "4. Testing eager loading...\n";
    $geofenceWithRelations = Geofence::with(['devices', 'alerts'])->first();
    echo "   Successfully loaded with relationships\n";

    echo "\n✅ All tests passed! The geofence relationships are working correctly.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 