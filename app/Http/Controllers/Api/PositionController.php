<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Position;
use App\Models\Trip;
use App\Models\Alert;
use App\Models\Geofence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'course' => 'nullable|numeric|between:0,360',
            'ignition' => 'nullable|boolean',
            'battery_level' => 'nullable|numeric|between:0,100',
            'timestamp' => 'nullable|date',
            'api_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        // Find device by unique_id and verify API key
        $device = Device::where('unique_id', $request->device_id)
            ->where('api_key', $request->api_key)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            Log::warning('Invalid device or API key', [
                'device_id' => $request->device_id,
                'api_key' => substr($request->api_key, 0, 10) . '...',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Invalid device ID or API key'
            ], 401);
        }

        try {
            // Update device position
            $device->updatePosition([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'speed' => $request->speed ?? 0,
                'altitude' => $request->altitude ?? 0,
                'course' => $request->course ?? 0,
                'ignition' => $request->boolean('ignition'),
                'battery_level' => $request->battery_level,
                'timestamp' => $request->timestamp ? \Carbon\Carbon::parse($request->timestamp) : now(),
            ]);

            // Check for trip detection
            $this->detectTrip($device);

            Log::info('Position updated successfully', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'speed' => $request->speed
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Position updated successfully',
                'device_id' => $device->id,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating position', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }

    private function processAlerts($device, $position)
    {
        // Speed limit alert (example: 80 km/h)
        if ($position->speed && $position->speed > 80) {
            $this->createAlert($device, 'speed_limit_exceeded', 'Speed Limit Exceeded', 
                "Device {$device->name} exceeded speed limit: {$position->formatted_speed}");
        }

        // Ignition status change
        $previousPosition = $device->positions()->where('id', '!=', $position->id)->latest('timestamp')->first();
        if ($previousPosition && $previousPosition->ignition !== $position->ignition) {
            $type = $position->ignition ? 'ignition_on' : 'ignition_off';
            $message = $position->ignition ? 'Ignition turned ON' : 'Ignition turned OFF';
            $this->createAlert($device, $type, $message, "Device {$device->name}: {$message}");
        }

        // Battery low alert
        if ($position->battery_level && $position->battery_level < 20) {
            $this->createAlert($device, 'battery_low', 'Low Battery', 
                "Device {$device->name} battery level: {$position->formatted_battery}");
        }
    }

    private function processGeofencing($device, $position)
    {
        $geofences = $device->geofences()->where('is_active', true)->get();

        foreach ($geofences as $geofence) {
            $isInside = $geofence->isPointInside($position->latitude, $position->longitude);
            
            // Get previous position to check if device was inside/outside
            $previousPosition = $device->positions()->where('id', '!=', $position->id)->latest('timestamp')->first();
            
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

    private function processTripDetection($device, $position)
    {
        // Check if device is moving (speed > 5 km/h)
        $isMoving = $position->speed && $position->speed > 5;
        
        // Get active trip
        $activeTrip = $device->activeTrip()->first();
        
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
            // End trip if device has been stationary for more than 5 minutes
            $lastMovingPosition = $device->positions()
                ->where('speed', '>', 5)
                ->latest('timestamp')
                ->first();
                
            if ($lastMovingPosition && $position->timestamp->diffInMinutes($lastMovingPosition->timestamp) > 5) {
                $activeTrip->complete($position->latitude, $position->longitude);
            }
        }
    }

    private function createAlert($device, $type, $title, $message)
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

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'positions' => 'required|array|min:1|max:100',
            'positions.*.device_id' => 'required|string',
            'positions.*.latitude' => 'required|numeric|between:-90,90',
            'positions.*.longitude' => 'required|numeric|between:-180,180',
            'positions.*.speed' => 'nullable|numeric|min:0',
            'positions.*.altitude' => 'nullable|numeric',
            'positions.*.course' => 'nullable|numeric|between:0,360',
            'positions.*.ignition' => 'nullable|boolean',
            'positions.*.battery_level' => 'nullable|numeric|between:0,100',
            'positions.*.timestamp' => 'nullable|date',
            'api_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($request->positions as $positionData) {
            try {
                $device = Device::where('unique_id', $positionData['device_id'])
                    ->where('api_key', $request->api_key)
                    ->where('is_active', true)
                    ->first();

                if (!$device) {
                    $results[] = [
                        'device_id' => $positionData['device_id'],
                        'success' => false,
                        'error' => 'Invalid device ID or API key'
                    ];
                    $errorCount++;
                    continue;
                }

                $device->updatePosition([
                    'latitude' => $positionData['latitude'],
                    'longitude' => $positionData['longitude'],
                    'speed' => $positionData['speed'] ?? 0,
                    'altitude' => $positionData['altitude'] ?? 0,
                    'course' => $positionData['course'] ?? 0,
                    'ignition' => $positionData['ignition'] ?? false,
                    'battery_level' => $positionData['battery_level'] ?? null,
                    'timestamp' => isset($positionData['timestamp']) ? \Carbon\Carbon::parse($positionData['timestamp']) : now(),
                ]);

                $this->detectTrip($device);

                $results[] = [
                    'device_id' => $positionData['device_id'],
                    'success' => true,
                    'message' => 'Position updated successfully'
                ];
                $successCount++;

            } catch (\Exception $e) {
                $results[] = [
                    'device_id' => $positionData['device_id'],
                    'success' => false,
                    'error' => 'Internal error'
                ];
                $errorCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk update completed. Success: {$successCount}, Errors: {$errorCount}",
            'results' => $results,
            'summary' => [
                'total' => count($request->positions),
                'success' => $successCount,
                'errors' => $errorCount
            ]
        ]);
    }

    private function detectTrip(Device $device)
    {
        $latestPosition = $device->positions()->latest('timestamp')->first();
        $activeTrip = $device->activeTrip()->first();

        // If no active trip and ignition is on, start a new trip
        if (!$activeTrip && $latestPosition->ignition) {
            $device->trips()->create([
                'start_time' => $latestPosition->timestamp,
                'start_lat' => $latestPosition->latitude,
                'start_lng' => $latestPosition->longitude,
                'status' => 'active',
            ]);
        }
        // If there's an active trip and ignition is off, end the trip
        elseif ($activeTrip && !$latestPosition->ignition) {
            $activeTrip->update([
                'end_time' => $latestPosition->timestamp,
                'end_lat' => $latestPosition->latitude,
                'end_lng' => $latestPosition->longitude,
                'status' => 'completed',
                'distance' => $this->calculateTripDistance($activeTrip->id),
                'duration_minutes' => $activeTrip->start_time->diffInMinutes($latestPosition->timestamp),
            ]);
        }
    }

    private function calculateTripDistance($tripId)
    {
        $trip = \App\Models\Trip::find($tripId);
        if (!$trip) return 0;

        $positions = $trip->device->positions()
            ->whereBetween('timestamp', [$trip->start_time, $trip->end_time])
            ->orderBy('timestamp')
            ->get();

        $distance = 0;
        $prevPosition = null;

        foreach ($positions as $position) {
            if ($prevPosition) {
                $distance += $this->calculateDistance(
                    $prevPosition->latitude,
                    $prevPosition->longitude,
                    $position->latitude,
                    $position->longitude
                );
            }
            $prevPosition = $position;
        }

        return $distance;
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    public function getDeviceStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'api_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $device = Device::where('unique_id', $request->device_id)
            ->where('api_key', $request->api_key)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return response()->json([
                'error' => 'Invalid device ID or API key'
            ], 401);
        }

        return response()->json([
            'device_id' => $device->unique_id,
            'name' => $device->name,
            'status' => $device->status,
            'last_update' => $device->last_update_time?->toISOString(),
            'position' => [
                'latitude' => $device->last_lat,
                'longitude' => $device->last_lng,
                'speed' => $device->last_speed,
                'ignition' => $device->ignition,
                'battery_level' => $device->battery_level,
            ],
            'settings' => [
                'update_interval' => 30, // seconds
                'speed_limit' => $device->user->speed_limit,
            ]
        ]);
    }
} 