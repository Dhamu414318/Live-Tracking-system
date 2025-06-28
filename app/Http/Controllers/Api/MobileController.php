<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Position;
use App\Models\Trip;
use App\Models\Alert;
use App\Models\Geofence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MobileController extends Controller
{
    /**
     * Single API endpoint for mobile app tracking
     * Handles all data processing automatically
     */
    public function track(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'api_key' => 'required|string',
            'is_tracking' => 'required|boolean', // ON/OFF toggle
            'latitude' => 'required_if:is_tracking,true|numeric|between:-90,90',
            'longitude' => 'required_if:is_tracking,true|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'course' => 'nullable|numeric|between:0,360',
            'ignition' => 'nullable|boolean',
            'battery_level' => 'nullable|numeric|between:0,100',
            'timestamp' => 'nullable|date',
            'user_email' => 'nullable|email', // For user identification
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            // Find or create device
            $device = $this->findOrCreateDevice($request);
            
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid device or API key'
                ], 401);
            }

            // If tracking is OFF, just update device status
            if (!$request->boolean('is_tracking')) {
                $device->update([
                    'status' => 'offline',
                    'last_update_time' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Tracking stopped',
                    'device_id' => $device->id,
                    'status' => 'offline'
                ]);
            }

            // If tracking is ON, process all data
            $position = $this->processPositionData($device, $request);
            
            // Process all automatic features
            $this->processTripDetection($device, $position);
            $this->processAlerts($device, $position);
            $this->processGeofencing($device, $position);

            Log::info('Mobile tracking data processed successfully', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'is_tracking' => $request->boolean('is_tracking'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tracking data processed successfully',
                'device_id' => $device->id,
                'status' => 'online',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing mobile tracking data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Find or create device based on mobile app data
     */
    private function findOrCreateDevice(Request $request)
    {
        // First try to find existing device
        $device = Device::where('unique_id', $request->device_id)
            ->where('api_key', $request->api_key)
            ->first();

        if ($device) {
            return $device;
        }

        // If device doesn't exist, create it
        if ($request->user_email) {
            $user = User::where('email', $request->user_email)->first();
            
            if (!$user) {
                // Create user if doesn't exist
                $user = User::create([
                    'name' => 'Mobile User',
                    'email' => $request->user_email,
                    'password' => bcrypt('mobile123'), // Default password
                    'role' => 'user',
                    'timezone' => 'UTC',
                    'units' => 'km',
                    'speed_limit' => 80,
                    'alert_preferences' => [
                        'speed_limit_exceeded' => true,
                        'ignition_on' => true,
                        'ignition_off' => true,
                        'battery_low' => true,
                        'device_offline' => true,
                        'geofence_enter' => true,
                        'geofence_exit' => true,
                    ]
                ]);
            }

            // Create device
            $device = Device::create([
                'user_id' => $user->id,
                'name' => 'Mobile Device ' . substr($request->device_id, -4),
                'unique_id' => $request->device_id,
                'api_key' => $request->api_key,
                'status' => 'online',
                'is_active' => true,
                'last_update_time' => now(),
            ]);

            return $device;
        }

        return null;
    }

    /**
     * Process and store position data
     */
    private function processPositionData(Device $device, Request $request)
    {
        $timestamp = $request->timestamp ? Carbon::parse($request->timestamp) : now();
        
        // Calculate distance from last position
        $lastPosition = $device->positions()->latest('timestamp')->first();
        $distance = 0;
        
        if ($lastPosition) {
            $distance = $this->calculateDistance(
                $lastPosition->latitude, $lastPosition->longitude,
                $request->latitude, $request->longitude
            );
        }

        // Create position record
        $position = Position::create([
            'device_id' => $device->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'speed' => $request->speed ?? 0,
            'altitude' => $request->altitude ?? 0,
            'course' => $request->course ?? 0,
            'ignition' => $request->boolean('ignition'),
            'battery_level' => $request->battery_level,
            'timestamp' => $timestamp,
            'distance' => $distance,
        ]);

        // Update device with latest position
        $device->update([
            'status' => 'online',
            'last_lat' => $request->latitude,
            'last_lng' => $request->longitude,
            'last_speed' => $request->speed ?? 0,
            'last_course' => $request->course ?? 0,
            'ignition' => $request->boolean('ignition'),
            'battery_level' => $request->battery_level,
            'last_update_time' => $timestamp,
        ]);

        return $position;
    }

    /**
     * Process trip detection automatically
     */
    private function processTripDetection(Device $device, Position $position)
    {
        $isMoving = $position->speed && $position->speed > 5; // 5 km/h threshold
        $activeTrip = $device->trips()->where('status', 'active')->first();

        if ($isMoving && !$activeTrip) {
            // Start new trip
            Trip::create([
                'device_id' => $device->id,
                'start_time' => $position->timestamp,
                'start_lat' => $position->latitude,
                'start_lng' => $position->longitude,
                'status' => 'active',
            ]);
        } elseif (!$isMoving && $activeTrip) {
            // Check if device has been stationary for more than 5 minutes
            $lastMovingPosition = $device->positions()
                ->where('speed', '>', 5)
                ->latest('timestamp')
                ->first();

            if ($lastMovingPosition && $position->timestamp->diffInMinutes($lastMovingPosition->timestamp) > 5) {
                // End trip
                $activeTrip->update([
                    'end_time' => $position->timestamp,
                    'end_lat' => $position->latitude,
                    'end_lng' => $position->longitude,
                    'status' => 'completed',
                ]);

                // Calculate trip statistics
                $this->calculateTripStatistics($activeTrip);
            }
        }
    }

    /**
     * Process automatic alerts
     */
    private function processAlerts(Device $device, Position $position)
    {
        // Speed limit alert (80 km/h default)
        if ($position->speed && $position->speed > 80) {
            $this->createAlert($device, 'speed_limit_exceeded', 'Speed Limit Exceeded', 
                "Device {$device->name} exceeded speed limit: " . number_format($position->speed, 1) . " km/h");
        }

        // Ignition status change
        $previousPosition = $device->positions()
            ->where('id', '!=', $position->id)
            ->latest('timestamp')
            ->first();

        if ($previousPosition && $previousPosition->ignition !== $position->ignition) {
            $type = $position->ignition ? 'ignition_on' : 'ignition_off';
            $message = $position->ignition ? 'Ignition turned ON' : 'Ignition turned OFF';
            $this->createAlert($device, $type, $message, "Device {$device->name}: {$message}");
        }

        // Battery low alert
        if ($position->battery_level && $position->battery_level < 20) {
            $this->createAlert($device, 'battery_low', 'Low Battery', 
                "Device {$device->name} battery level: {$position->battery_level}%");
        }
    }

    /**
     * Process geofencing
     */
    private function processGeofencing(Device $device, Position $position)
    {
        $geofences = $device->geofences()->where('is_active', true)->get();

        foreach ($geofences as $geofence) {
            $isInside = $geofence->isPointInside($position->latitude, $position->longitude);
            
            $previousPosition = $device->positions()
                ->where('id', '!=', $position->id)
                ->latest('timestamp')
                ->first();
            
            if ($previousPosition) {
                $wasInside = $geofence->isPointInside($previousPosition->latitude, $previousPosition->longitude);
                
                if (!$wasInside && $isInside) {
                    // Device entered geofence
                    $this->createAlert($device, 'geofence_enter', 'Geofence Entered', 
                        "Device {$device->name} entered geofence: {$geofence->name}");
                } elseif ($wasInside && !$isInside) {
                    // Device exited geofence
                    $this->createAlert($device, 'geofence_exit', 'Geofence Exited', 
                        "Device {$device->name} exited geofence: {$geofence->name}");
                }
            }
        }
    }

    /**
     * Create alert
     */
    private function createAlert(Device $device, $type, $title, $message)
    {
        Alert::create([
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'triggered_at' => now(),
        ]);
    }

    /**
     * Calculate trip statistics
     */
    private function calculateTripStatistics(Trip $trip)
    {
        $positions = Position::where('device_id', $trip->device_id)
            ->whereBetween('timestamp', [$trip->start_time, $trip->end_time])
            ->orderBy('timestamp')
            ->get();

        $totalDistance = $positions->sum('distance');
        $avgSpeed = $positions->avg('speed');
        $maxSpeed = $positions->max('speed');
        $duration = $trip->start_time->diffInSeconds($trip->end_time);

        $trip->update([
            'distance' => $totalDistance,
            'avg_speed' => $avgSpeed,
            'max_speed' => $maxSpeed,
            'duration_seconds' => $duration,
        ]);
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
} 