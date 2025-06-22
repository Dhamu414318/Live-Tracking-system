<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API endpoints for device position updates
Route::post('/positions', [PositionController::class, 'store'])->name('api.positions.store');
Route::post('/positions/bulk', [PositionController::class, 'bulkStore'])->name('api.positions.bulk');
Route::get('/device/status', [PositionController::class, 'getDeviceStatus'])->name('api.device.status');

// Authenticated API endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Device endpoints
    Route::get('/devices', function (Request $request) {
        $user = $request->user();
        return $user->isAdmin() 
            ? \App\Models\Device::with('user')->get()
            : $user->devices;
    });
    
    Route::get('/devices/{device}/positions', function (Request $request, $deviceId) {
        $user = $request->user();
        $device = $user->isAdmin() 
            ? \App\Models\Device::find($deviceId)
            : $user->devices()->find($deviceId);
            
        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }
        
        return $device->positions()
            ->orderBy('timestamp', 'desc')
            ->take(100)
            ->get();
    });
    
    // Alert endpoints
    Route::get('/alerts', function (Request $request) {
        $user = $request->user();
        return $user->alerts()
            ->with('device')
            ->orderBy('triggered_at', 'desc')
            ->paginate(20);
    });
    
    Route::post('/alerts/{alert}/mark-read', function (Request $request, $alertId) {
        $user = $request->user();
        $alert = $user->alerts()->find($alertId);
        
        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }
        
        $alert->update(['is_read' => true]);
        return response()->json(['success' => true]);
    });
    
    // Trip endpoints
    Route::get('/trips', function (Request $request) {
        $user = $request->user();
        return $user->isAdmin() 
            ? \App\Models\Trip::with('device.user')->paginate(20)
            : $user->trips()->with('device')->paginate(20);
    });
    
    // Geofence endpoints
    Route::get('/geofences', function (Request $request) {
        $user = $request->user();
        return $user->isAdmin() 
            ? \App\Models\Geofence::with('user')->get()
            : $user->geofences;
    });
    
    // Statistics endpoints
    Route::get('/stats', function (Request $request) {
        $user = $request->user();
        
        $devices = $user->isAdmin() ? \App\Models\Device::all() : $user->devices;
        
        return response()->json([
            'total_devices' => $devices->count(),
            'online_devices' => $devices->where('status', 'online')->count(),
            'offline_devices' => $devices->where('status', 'offline')->count(),
            'moving_devices' => $devices->where('last_speed', '>', 0)->count(),
            'total_alerts' => $user->alerts()->count(),
            'unread_alerts' => $user->alerts()->unread()->count(),
            'total_trips' => $user->isAdmin() 
                ? \App\Models\Trip::count() 
                : $user->trips()->count(),
            'total_geofences' => $user->geofences()->count(),
        ]);
    });
    
    // Live updates endpoint
    Route::get('/live-updates', function (Request $request) {
        $user = $request->user();
        
        $devices = $user->isAdmin() 
            ? \App\Models\Device::with(['user', 'latestPosition'])->get()
            : $user->devices()->with('latestPosition')->get();

        return $devices->map(function ($device) {
            return [
                'id' => $device->id,
                'name' => $device->name,
                'unique_id' => $device->unique_id,
                'status' => $device->status,
                'last_lat' => $device->last_lat,
                'last_lng' => $device->last_lng,
                'last_speed' => $device->last_speed,
                'ignition' => $device->ignition,
                'battery_level' => $device->battery_level,
                'last_update_time' => $device->last_update_time,
                'user_name' => $device->user->name ?? null,
            ];
        });
    });
    
    // Unread alerts count
    Route::get('/unread-alerts-count', function (Request $request) {
        $user = $request->user();
        return response()->json(['count' => $user->getUnreadAlertsCount()]);
    });
});

// WebSocket endpoint for real-time updates
Route::get('/websocket', function () {
    return response()->json(['message' => 'WebSocket endpoint']);
})->name('api.websocket');

// Reports API
Route::prefix('reports')->group(function () {
    Route::get('/combined', [ReportController::class, 'combined']);
    Route::get('/route', [ReportController::class, 'route']);
    Route::get('/events', [ReportController::class, 'events']);
    Route::get('/trips', [ReportController::class, 'trips']);
    Route::get('/stops', [ReportController::class, 'stops']);
    Route::get('/summary', [ReportController::class, 'summary']);
    Route::get('/chart', [ReportController::class, 'chart']);
    Route::get('/replay', [ReportController::class, 'replay']);
});
