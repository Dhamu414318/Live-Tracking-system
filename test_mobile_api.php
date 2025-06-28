<?php
/**
 * Mobile API Test Script
 * This demonstrates how the mobile app works with just one API endpoint
 */

$baseUrl = 'http://localhost/my-tracking-backend/public/api';

// Test data for mobile app
$deviceId = 'MOBILE_' . uniqid();
$apiKey = 'mobile_key_' . substr(md5(uniqid()), 0, 16);
$userEmail = 'mobileuser@example.com';

echo "🚀 Mobile API Test Script\n";
echo "========================\n\n";

// Test 1: Start tracking (ON)
echo "📱 Test 1: Starting tracking (ON)\n";
$trackingData = [
    'device_id' => $deviceId,
    'api_key' => $apiKey,
    'is_tracking' => true,
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'speed' => 25.5,
    'altitude' => 100,
    'course' => 180,
    'ignition' => true,
    'battery_level' => 85,
    'timestamp' => date('Y-m-d H:i:s'),
    'user_email' => $userEmail
];

$response = sendRequest($baseUrl . '/mobile/track', $trackingData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Continue tracking with movement
echo "📱 Test 2: Continue tracking (moving)\n";
$trackingData['latitude'] = 40.7130;
$trackingData['longitude'] = -74.0062;
$trackingData['speed'] = 30.0;
$trackingData['timestamp'] = date('Y-m-d H:i:s');

$response = sendRequest($baseUrl . '/mobile/track', $trackingData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Speed limit exceeded
echo "📱 Test 3: Speed limit exceeded\n";
$trackingData['latitude'] = 40.7135;
$trackingData['longitude'] = -74.0065;
$trackingData['speed'] = 85.0; // Exceeds 80 km/h limit
$trackingData['timestamp'] = date('Y-m-d H:i:s');

$response = sendRequest($baseUrl . '/mobile/track', $trackingData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Stop tracking (OFF)
echo "📱 Test 4: Stop tracking (OFF)\n";
$trackingData['is_tracking'] = false;
unset($trackingData['latitude']);
unset($trackingData['longitude']);
unset($trackingData['speed']);

$response = sendRequest($baseUrl . '/mobile/track', $trackingData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

echo "✅ Mobile API tests completed!\n";
echo "Check the web dashboard to see the data: http://localhost/my-tracking-backend/public\n";

function sendRequest($url, $data) {
    echo "Sending request to: $url\n";
    echo "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "cURL Error: $error\n";
    }
    
    return [
        'status_code' => $httpCode,
        'response' => json_decode($response, true),
        'raw_response' => $response
    ];
}
?> 