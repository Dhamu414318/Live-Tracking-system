<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Device;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TripController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $trips = Trip::with('device')->latest()->paginate(50);
        $devices = Device::all();
        
        // Calculate stats
        $totalDistance = $trips->sum('distance');
        $totalSeconds = $trips->sum('duration_seconds');
        $avgSpeed = $trips->avg('avg_speed') ?? 0;
        
        // Convert total time to readable format
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $totalTime = "{$hours}h {$minutes}m";
        
        return view('trips.index', compact('trips', 'devices', 'totalDistance', 'totalTime', 'avgSpeed'));
    }

    public function show(Trip $trip)
    {
        $trip->load(['device', 'positions' => function($query) {
            $query->orderBy('timestamp');
        }]);
        
        return view('trips.show', compact('trip'));
    }

    public function map(Trip $trip)
    {
        $trip->load(['device', 'positions' => function($query) {
            $query->orderBy('timestamp');
        }]);
        
        return view('trips.map', compact('trip'));
    }

    public function destroy(Trip $trip)
    {
        $trip->delete();
        return redirect()->route('trips.index')->with('success', 'Trip deleted successfully.');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $device = Device::findOrFail($request->device_id);
        $positions = Position::where('device_id', $device->id)
            ->whereBetween('timestamp', [$request->start_date, $request->end_date])
            ->orderBy('timestamp')
            ->get();

        if ($positions->count() < 2) {
            return redirect()->back()->with('error', 'Not enough position data to generate trip.');
        }

        // Group positions into trips based on time gaps
        $trips = $this->groupPositionsIntoTrips($positions);

        foreach ($trips as $tripData) {
            Trip::create($tripData);
        }

        return redirect()->route('trips.index')->with('success', 'Trips generated successfully.');
    }

    private function groupPositionsIntoTrips($positions)
    {
        $trips = [];
        $currentTrip = null;
        $tripGap = 300; // 5 minutes gap to consider new trip

        foreach ($positions as $position) {
            if (!$currentTrip) {
                $currentTrip = [
                    'device_id' => $position->device_id,
                    'start_time' => $position->timestamp,
                    'start_lat' => $position->latitude,
                    'start_lng' => $position->longitude,
                    'positions' => [$position],
                ];
            } else {
                $lastPosition = end($currentTrip['positions']);
                $timeDiff = Carbon::parse($position->timestamp)->diffInSeconds($lastPosition->timestamp);

                if ($timeDiff > $tripGap) {
                    // End current trip and start new one
                    $currentTrip = $this->finalizeTrip($currentTrip);
                    $trips[] = $currentTrip;

                    $currentTrip = [
                        'device_id' => $position->device_id,
                        'start_time' => $position->timestamp,
                        'start_lat' => $position->latitude,
                        'start_lng' => $position->longitude,
                        'positions' => [$position],
                    ];
                } else {
                    $currentTrip['positions'][] = $position;
                }
            }
        }

        // Finalize last trip
        if ($currentTrip && count($currentTrip['positions']) > 1) {
            $currentTrip = $this->finalizeTrip($currentTrip);
            $trips[] = $currentTrip;
        }

        return $trips;
    }

    private function finalizeTrip($tripData)
    {
        $positions = $tripData['positions'];
        $lastPosition = end($positions);

        $tripData['end_time'] = $lastPosition->timestamp;
        $tripData['end_lat'] = $lastPosition->latitude;
        $tripData['end_lng'] = $lastPosition->longitude;

        // Calculate distance
        $distance = 0;
        $speeds = [];
        
        for ($i = 1; $i < count($positions); $i++) {
            $prev = $positions[$i - 1];
            $curr = $positions[$i];
            
            $distance += $this->calculateDistance($prev->latitude, $prev->longitude, $curr->latitude, $curr->longitude);
            
            if ($curr->speed > 0) {
                $speeds[] = $curr->speed;
            }
        }

        $tripData['distance'] = round($distance, 2);
        $tripData['average_speed'] = count($speeds) > 0 ? round(array_sum($speeds) / count($speeds), 1) : 0;
        $tripData['max_speed'] = count($speeds) > 0 ? round(max($speeds), 1) : 0;
        $tripData['duration_seconds'] = Carbon::parse($tripData['end_time'])->diffInSeconds($tripData['start_time']);
        $tripData['duration'] = $this->formatDuration($tripData['duration_seconds']);

        unset($tripData['positions']);
        return $tripData;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344; // Convert to kilometers
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        } else {
            return sprintf('%02d:%02d', $minutes, $secs);
        }
    }

    public function getTripData(Trip $trip)
    {
        $user = Auth::user();
        
        // Check if user has access to this trip
        if (!$user->isAdmin() && $trip->device->user_id !== $user->id) {
            abort(403);
        }

        return response()->json([
            'id' => $trip->id,
            'device_name' => $trip->device->name,
            'start_time' => $trip->start_time ? $trip->start_time->format('M d, Y H:i') : 'N/A',
            'end_time' => $trip->end_time ? $trip->end_time->format('M d, Y H:i') : 'N/A',
            'formatted_duration' => $trip->formatted_duration,
            'formatted_distance' => $trip->formatted_distance,
            'formatted_max_speed' => $trip->formatted_max_speed,
            'formatted_avg_speed' => $trip->formatted_avg_speed,
            'formatted_start_location' => $trip->formatted_start_location,
            'formatted_end_location' => $trip->formatted_end_location,
            'status' => $trip->status,
        ]);
    }

    public function getTripRoute(Trip $trip)
    {
        $user = Auth::user();
        
        // Check if user has access to this trip
        if (!$user->isAdmin() && $trip->device->user_id !== $user->id) {
            abort(403);
        }

        $positions = $trip->positions()->get();
        
        return response()->json([
            'trip' => $trip,
            'positions' => $positions->map(function($position) {
                return [
                    'latitude' => $position->latitude,
                    'longitude' => $position->longitude,
                    'timestamp' => $position->timestamp,
                    'speed' => $position->speed,
                ];
            })
        ]);
    }

    public function filter(Request $request)
    {
        $query = Trip::with('device');
        
        if ($request->device_id) {
            $query->where('device_id', $request->device_id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->date_range) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('start_time', today());
                    break;
                case 'yesterday':
                    $query->whereDate('start_time', today()->subDay());
                    break;
                case 'week':
                    $query->where('start_time', '>=', now()->subDays(7));
                    break;
                case 'month':
                    $query->where('start_time', '>=', now()->subDays(30));
                    break;
            }
        }
        
        $trips = $query->latest()->get();
        
        return response()->json($trips->map(function($trip) {
            return [
                'id' => $trip->id,
                'device_name' => $trip->device->name,
                'start_time' => $trip->start_time ? $trip->start_time->format('M d, Y H:i') : 'N/A',
                'end_time' => $trip->end_time ? $trip->end_time->format('M d, Y H:i') : 'N/A',
                'formatted_duration' => $trip->formatted_duration,
                'formatted_distance' => $trip->formatted_distance,
                'formatted_max_speed' => $trip->formatted_max_speed,
                'formatted_avg_speed' => $trip->formatted_avg_speed,
                'formatted_start_location' => $trip->formatted_start_location,
                'formatted_end_location' => $trip->formatted_end_location,
                'status' => $trip->status,
            ];
        }));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->isAdmin() 
            ? Trip::with('device.user')
            : Trip::with('device')->whereIn('device_id', $user->devices->pluck('id'));
        
        // Apply filters
        if ($request->has('device_id')) {
            $deviceId = $request->device_id;
            if ($user->isAdmin() || $user->devices()->where('id', $deviceId)->exists()) {
                $query->where('device_id', $deviceId);
            }
        }
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }
        
        $trips = $query->latest('start_time')->get();
        
        // Generate CSV
        $filename = 'trips_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($trips) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Trip ID',
                'Device',
                'Start Time',
                'End Time',
                'Duration',
                'Distance (km)',
                'Max Speed (km/h)',
                'Avg Speed (km/h)',
                'Start Location',
                'End Location',
                'Status'
            ]);
            
            // CSV data
            foreach ($trips as $trip) {
                fputcsv($file, [
                    $trip->id,
                    $trip->device->name,
                    $trip->start_time->format('Y-m-d H:i:s'),
                    $trip->end_time ? $trip->end_time->format('Y-m-d H:i:s') : '',
                    $trip->formatted_duration,
                    $trip->distance,
                    $trip->max_speed,
                    $trip->avg_speed,
                    $trip->formatted_start_location,
                    $trip->formatted_end_location,
                    $trip->status,
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
} 