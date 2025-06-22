<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Geofence;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get devices based on user role
        $devices = $user->isAdmin() 
            ? Device::with(['user', 'latestPosition'])->get()
            : $user->devices()->with('latestPosition')->get();

        // Get geofences
        $geofences = $user->isAdmin() 
            ? Geofence::with('user')->get()
            : $user->geofences;

        return view('map.index', compact('devices', 'geofences'));
    }

    public function getDevicePositions(Request $request)
    {
        $user = Auth::user();
        $deviceId = $request->input('device_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Verify user has access to this device
        $device = $user->isAdmin() 
            ? Device::find($deviceId)
            : $user->devices()->find($deviceId);

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $query = $device->positions()->orderBy('timestamp');

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        $positions = $query->get()->map(function ($position) {
            return [
                'id' => $position->id,
                'latitude' => $position->latitude,
                'longitude' => $position->longitude,
                'speed' => $position->speed,
                'altitude' => $position->altitude,
                'course' => $position->course,
                'ignition' => $position->ignition,
                'battery_level' => $position->battery_level,
                'timestamp' => $position->timestamp->toISOString(),
            ];
        });

        return response()->json($positions);
    }

    public function getDeviceHistory(Request $request)
    {
        $user = Auth::user();
        $deviceId = $request->input('device_id');
        $date = $request->input('date', today()->format('Y-m-d'));
        
        $device = $user->isAdmin() 
            ? Device::find($deviceId)
            : $user->devices()->find($deviceId);

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $positions = $device->positions()
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp')
            ->get()
            ->map(function ($position) {
                return [
                    'latitude' => $position->latitude,
                    'longitude' => $position->longitude,
                    'speed' => $position->speed,
                    'timestamp' => $position->timestamp->toISOString(),
                ];
            });

        return response()->json($positions);
    }

    public function getGeofenceDevices(Request $request)
    {
        $user = Auth::user();
        $geofenceId = $request->input('geofence_id');
        
        $geofence = $user->isAdmin() 
            ? Geofence::find($geofenceId)
            : $user->geofences()->find($geofenceId);

        if (!$geofence) {
            return response()->json(['error' => 'Geofence not found'], 404);
        }

        $devices = $geofence->devices()->with('latestPosition')->get();

        return response()->json($devices);
    }

    public function getMapBounds()
    {
        $user = Auth::user();
        
        $devices = $user->isAdmin() 
            ? Device::whereNotNull('last_lat')->whereNotNull('last_lng')->get()
            : $user->devices()->whereNotNull('last_lat')->whereNotNull('last_lng')->get();

        if ($devices->isEmpty()) {
            return response()->json([
                'bounds' => [
                    'north' => 40.7128,
                    'south' => 40.7128,
                    'east' => -74.0060,
                    'west' => -74.0060,
                ]
            ]);
        }

        $lats = $devices->pluck('last_lat')->filter();
        $lngs = $devices->pluck('last_lng')->filter();

        return response()->json([
            'bounds' => [
                'north' => $lats->max(),
                'south' => $lats->min(),
                'east' => $lngs->max(),
                'west' => $lngs->min(),
            ]
        ]);
    }

    public function exportTrack(Request $request)
    {
        $user = Auth::user();
        $deviceId = $request->input('device_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = $request->input('format', 'gpx');
        
        $device = $user->isAdmin() 
            ? Device::find($deviceId)
            : $user->devices()->find($deviceId);

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $query = $device->positions()->orderBy('timestamp');

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        $positions = $query->get();

        if ($format === 'gpx') {
            return $this->exportToGpx($device, $positions);
        } elseif ($format === 'kml') {
            return $this->exportToKml($device, $positions);
        }

        return response()->json(['error' => 'Unsupported format'], 400);
    }

    private function exportToGpx($device, $positions)
    {
        $gpx = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $gpx .= '<gpx version="1.1" creator="GPS Tracking System">' . "\n";
        $gpx .= '  <trk>' . "\n";
        $gpx .= '    <name>' . htmlspecialchars($device->name) . '</name>' . "\n";
        $gpx .= '    <trkseg>' . "\n";

        foreach ($positions as $position) {
            $gpx .= '      <trkpt lat="' . $position->latitude . '" lon="' . $position->longitude . '">' . "\n";
            $gpx .= '        <time>' . $position->timestamp->toISOString() . '</time>' . "\n";
            if ($position->speed) {
                $gpx .= '        <speed>' . $position->speed . '</speed>' . "\n";
            }
            if ($position->altitude) {
                $gpx .= '        <ele>' . $position->altitude . '</ele>' . "\n";
            }
            $gpx .= '      </trkpt>' . "\n";
        }

        $gpx .= '    </trkseg>' . "\n";
        $gpx .= '  </trk>' . "\n";
        $gpx .= '</gpx>';

        return response($gpx)
            ->header('Content-Type', 'application/gpx+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $device->name . '_track.gpx"');
    }

    private function exportToKml($device, $positions)
    {
        $kml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $kml .= '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";
        $kml .= '  <Document>' . "\n";
        $kml .= '    <name>' . htmlspecialchars($device->name) . ' Track</name>' . "\n";
        $kml .= '    <Placemark>' . "\n";
        $kml .= '      <name>' . htmlspecialchars($device->name) . '</name>' . "\n";
        $kml .= '      <LineString>' . "\n";
        $kml .= '        <coordinates>' . "\n";

        foreach ($positions as $position) {
            $kml .= '          ' . $position->longitude . ',' . $position->latitude;
            if ($position->altitude) {
                $kml .= ',' . $position->altitude;
            }
            $kml .= "\n";
        }

        $kml .= '        </coordinates>' . "\n";
        $kml .= '      </LineString>' . "\n";
        $kml .= '    </Placemark>' . "\n";
        $kml .= '  </Document>' . "\n";
        $kml .= '</kml>';

        return response($kml)
            ->header('Content-Type', 'application/vnd.google-earth.kml+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $device->name . '_track.kml"');
    }
} 