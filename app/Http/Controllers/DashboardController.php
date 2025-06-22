<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Alert;
use App\Models\Trip;
use App\Models\Geofence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get devices based on user role
        $devices = $user->isAdmin() ? Device::with('user')->get() : $user->devices;
        
        // Get statistics
        $stats = [
            'total_devices' => $devices->count(),
            'online_devices' => $devices->where('status', 'online')->count(),
            'offline_devices' => $devices->where('status', 'offline')->count(),
            'moving_devices' => $devices->where('last_speed', '>', 0)->count(),
            'total_alerts' => $user->alerts()->count(),
            'unread_alerts' => $user->alerts()->unread()->count(),
            'total_trips' => $user->isAdmin() ? Trip::count() : Trip::whereIn('device_id', $user->devices->pluck('id'))->count(),
            'total_geofences' => $user->geofences()->count(),
        ];

        // Get recent alerts
        $recentAlerts = $user->alerts()
            ->with('device')
            ->latest('triggered_at')
            ->take(5)
            ->get();

        // Get recent trips
        $recentTrips = $user->isAdmin() 
            ? Trip::with('device.user')->latest('start_time')->take(5)->get()
            : Trip::with('device')->whereIn('device_id', $user->devices->pluck('id'))->latest('start_time')->take(5)->get();

        return view('dashboard', compact('devices', 'stats', 'recentAlerts', 'recentTrips'));
    }

    public function map()
    {
        $user = Auth::user();
        
        // Get devices with their latest positions
        $devices = $user->isAdmin() 
            ? Device::with(['user', 'latestPosition'])->get()
            : $user->devices()->with('latestPosition')->get();

        // Get geofences
        $geofences = $user->isAdmin() 
            ? Geofence::with('user')->get()
            : $user->geofences;

        return view('map', compact('devices', 'geofences'));
    }

    public function getDevicePositions(Request $request)
    {
        $user = Auth::user();
        $deviceId = $request->input('device_id');
        
        // Verify user has access to this device
        $device = $user->isAdmin() 
            ? Device::find($deviceId)
            : $user->devices()->find($deviceId);

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $positions = $device->positions()
            ->orderBy('timestamp', 'desc')
            ->take(100)
            ->get()
            ->reverse()
            ->values();

        return response()->json($positions);
    }

    public function getLiveUpdates()
    {
        $user = Auth::user();
        
        // Get devices with their latest positions
        $devices = $user->isAdmin() 
            ? Device::with(['user', 'latestPosition'])->get()
            : $user->devices()->with('latestPosition')->get();

        $data = $devices->map(function ($device) {
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

        return response()->json($data);
    }
} 