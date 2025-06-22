<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Trip;
use App\Models\Alert;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', 'week');
        $deviceId = $request->get('device_id');
        
        // Get date range
        $dates = $this->getDateRange($dateRange);
        
        // Get devices
        $devices = Device::all();
        
        // Calculate stats
        $totalDistance = $this->calculateTotalDistance($dates['start'], $dates['end'], $deviceId);
        $totalTime = $this->calculateTotalTime($dates['start'], $dates['end'], $deviceId);
        $avgSpeed = $this->calculateAverageSpeed($dates['start'], $dates['end'], $deviceId);
        $totalAlerts = Alert::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
        
        // Get device stats
        $deviceStats = $this->getDeviceStats($dates['start'], $dates['end']);
        
        // Get recent trips
        $recentTrips = Trip::with('device')
            ->when($deviceId, function($query) use ($deviceId) {
                return $query->where('device_id', $deviceId);
            })
            ->latest()
            ->take(10)
            ->get();
        
        // Chart data
        $chartLabels = $this->getChartLabels($dates['start'], $dates['end']);
        $distanceData = $this->getDistanceData($dates['start'], $dates['end'], $deviceId);
        $speedLabels = ['0-20', '21-40', '41-60', '61-80', '80+'];
        $speedData = $this->getSpeedDistribution($dates['start'], $dates['end'], $deviceId);
        
        return view('reports.index', compact(
            'devices',
            'totalDistance',
            'totalTime',
            'avgSpeed',
            'totalAlerts',
            'deviceStats',
            'recentTrips',
            'chartLabels',
            'distanceData',
            'speedLabels',
            'speedData'
        ));
    }

    private function getDateRange($range)
    {
        switch ($range) {
            case 'today':
                return [
                    'start' => Carbon::today(),
                    'end' => Carbon::now()
                ];
            case 'yesterday':
                return [
                    'start' => Carbon::yesterday(),
                    'end' => Carbon::today()
                ];
            case 'week':
                return [
                    'start' => Carbon::now()->subDays(7),
                    'end' => Carbon::now()
                ];
            case 'month':
                return [
                    'start' => Carbon::now()->subDays(30),
                    'end' => Carbon::now()
                ];
            case 'quarter':
                return [
                    'start' => Carbon::now()->subMonths(3),
                    'end' => Carbon::now()
                ];
            case 'year':
                return [
                    'start' => Carbon::now()->subYear(),
                    'end' => Carbon::now()
                ];
            default:
                return [
                    'start' => Carbon::now()->subDays(7),
                    'end' => Carbon::now()
                ];
        }
    }

    private function calculateTotalDistance($start, $end, $deviceId = null)
    {
        $query = Trip::whereBetween('start_time', [$start, $end]);
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        
        return $query->sum('distance');
    }

    private function calculateTotalTime($start, $end, $deviceId = null)
    {
        $query = Trip::whereBetween('start_time', [$start, $end]);
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        
        $totalSeconds = $query->sum('duration_seconds') ?? 0;
        
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        
        return "{$hours}h {$minutes}m";
    }

    private function calculateAverageSpeed($start, $end, $deviceId = null)
    {
        $query = Trip::whereBetween('start_time', [$start, $end]);
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        
        return $query->avg('avg_speed') ?? 0;
    }

    private function getDeviceStats($start, $end)
    {
        $devices = Device::with(['trips' => function($query) use ($start, $end) {
            $query->whereBetween('start_time', [$start, $end]);
        }, 'alerts' => function($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }])->get();

        return $devices->map(function($device) {
            $totalDistance = $device->trips->sum('distance');
            $totalTime = $device->trips->sum('duration_seconds');
            $avgSpeed = $device->trips->avg('avg_speed') ?? 0;
            $stops = $device->trips->count();
            $alerts = $device->alerts->count();
            
            $hours = floor($totalTime / 3600);
            $minutes = floor(($totalTime % 3600) / 60);
            $timeFormatted = "{$hours}h {$minutes}m";
            
            return [
                'device_name' => $device->name,
                'distance' => round($totalDistance, 1),
                'time' => $timeFormatted,
                'avg_speed' => round($avgSpeed, 1),
                'stops' => $stops,
                'alerts' => $alerts,
            ];
        })->toArray();
    }

    private function getChartLabels($start, $end)
    {
        $labels = [];
        $current = $start->copy();
        
        while ($current <= $end) {
            $labels[] = $current->format('M d');
            $current->addDay();
        }
        
        return $labels;
    }

    private function getDistanceData($start, $end, $deviceId = null)
    {
        $data = [];
        $current = $start->copy();
        
        while ($current <= $end) {
            $query = Trip::whereDate('start_time', $current);
            
            if ($deviceId) {
                $query->where('device_id', $deviceId);
            }
            
            $data[] = round($query->sum('distance'), 1);
            $current->addDay();
        }
        
        return $data;
    }

    private function getSpeedDistribution($start, $end, $deviceId = null)
    {
        $query = Position::whereBetween('timestamp', [$start, $end]);
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        
        $positions = $query->get();
        
        $distribution = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '80+' => 0,
        ];
        
        foreach ($positions as $position) {
            $speed = $position->speed;
            
            if ($speed <= 20) {
                $distribution['0-20']++;
            } elseif ($speed <= 40) {
                $distribution['21-40']++;
            } elseif ($speed <= 60) {
                $distribution['41-60']++;
            } elseif ($speed <= 80) {
                $distribution['61-80']++;
            } else {
                $distribution['80+']++;
            }
        }
        
        return array_values($distribution);
    }

    public function export(Request $request)
    {
        // Implementation for exporting reports
        return response()->json(['message' => 'Export functionality to be implemented']);
    }

    public function device(Device $device, Request $request)
    {
        $dateRange = $request->get('date_range', 'week');
        $dates = $this->getDateRange($dateRange);
        
        $trips = $device->trips()
            ->whereBetween('start_time', [$dates['start'], $dates['end']])
            ->latest()
            ->get();
            
        $positions = $device->positions()
            ->whereBetween('timestamp', [$dates['start'], $dates['end']])
            ->latest()
            ->take(1000)
            ->get();
            
        $alerts = $device->alerts()
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->latest()
            ->get();
            
        return view('reports.device', compact('device', 'trips', 'positions', 'alerts', 'dates'));
    }

    // API Methods for Reports
    public function combined(Request $request)
    {
        $deviceId = $request->get('device_id');
        $period = $request->get('period', 'week');
        
        $dates = $this->getDateRange($period);
        
        $query = Position::with('device')
            ->whereBetween('timestamp', [$dates['start'], $dates['end']]);
            
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        
        $positions = $query->orderBy('timestamp')->get();
        
        return response()->json($positions->map(function($position) {
            return [
                'device_name' => $position->device->name,
                'fix_time' => $position->timestamp->format('M d, Y H:i:s'),
                'type' => 'GPS',
                'latitude' => $position->latitude,
                'longitude' => $position->longitude,
                'speed' => $position->speed . ' km/h',
                'course' => $position->course . '°',
                'altitude' => $position->altitude . ' m',
                'accuracy' => $position->accuracy . ' m',
                'valid' => $position->valid ? 'Yes' : 'No',
                'protocol' => $position->protocol,
                'server_time' => $position->created_at->format('M d, Y H:i:s'),
                'geofence' => $position->geofence_id ? 'In Geofence' : 'Outside',
                'battery_level' => $position->battery_level . '%',
                'charge' => $position->charge ? 'Charging' : 'Not Charging',
                'distance' => $position->distance . ' km',
                'motion' => $position->motion ? 'Moving' : 'Stopped',
            ];
        }));
    }

    public function route(Request $request)
    {
        $deviceId = $request->get('device_id');
        $period = $request->get('period', 'week');
        $columns = $request->get('columns', ['device', 'fixTime', 'latitude', 'longitude', 'speed']);
        
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }
        
        $dates = $this->getDateRange($period);
        
        $query = Position::with('device')
            ->whereBetween('timestamp', [$dates['start'], $dates['end']]);
            
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        
        $positions = $query->orderBy('timestamp')->get();
        
        return response()->json($positions->map(function($position) use ($columns) {
            $data = [];
            
            foreach ($columns as $column) {
                switch ($column) {
                    case 'device':
                        $data['device'] = $position->device->name;
                        break;
                    case 'fixTime':
                        $data['fixTime'] = $position->timestamp->format('M d, Y H:i:s');
                        break;
                    case 'latitude':
                        $data['latitude'] = $position->latitude;
                        break;
                    case 'longitude':
                        $data['longitude'] = $position->longitude;
                        break;
                    case 'speed':
                        $data['speed'] = $position->speed . ' km/h';
                        break;
                    case 'course':
                        $data['course'] = $position->course . '°';
                        break;
                    case 'altitude':
                        $data['altitude'] = $position->altitude . ' m';
                        break;
                    case 'accuracy':
                        $data['accuracy'] = $position->accuracy . ' m';
                        break;
                    case 'valid':
                        $data['valid'] = $position->valid ? 'Yes' : 'No';
                        break;
                    case 'protocol':
                        $data['protocol'] = $position->protocol;
                        break;
                    case 'serverTime':
                        $data['serverTime'] = $position->created_at->format('M d, Y H:i:s');
                        break;
                    case 'geoFence':
                        $data['geoFence'] = $position->geofence_id ? 'In Geofence' : 'Outside';
                        break;
                    case 'batteryLevel':
                        $data['batteryLevel'] = $position->battery_level . '%';
                        break;
                    case 'charge':
                        $data['charge'] = $position->charge ? 'Charging' : 'Not Charging';
                        break;
                    case 'distance':
                        $data['distance'] = $position->distance . ' km';
                        break;
                    case 'motion':
                        $data['motion'] = $position->motion ? 'Moving' : 'Stopped';
                        break;
                }
            }
            
            return $data;
        }));
    }

    public function events(Request $request)
    {
        $deviceId = $request->get('device_id');
        $period = $request->get('period', 'week');
        $eventType = $request->get('type');
        $columns = explode(',', $request->get('columns', 'fixTime,type,data'));

        $dates = $this->getDateRange($period);

        $query = Alert::with(['device', 'geofence'])
            ->whereBetween('triggered_at', [$dates['start'], $dates['end']]);

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        if ($eventType) {
            $query->where('type', $eventType);
        }

        $alerts = $query->latest('triggered_at')->get();

        $events = $alerts->map(function ($alert) use ($columns) {
            // Find the closest position to the alert time
            $position = Position::where('device_id', $alert->device_id)
                ->where('timestamp', '<=', $alert->triggered_at)
                ->orderBy('timestamp', 'desc')
                ->first();

            $eventData = [];
            $eventData['latitude'] = $position->latitude ?? null;
            $eventData['longitude'] = $position->longitude ?? null;

            foreach ($columns as $column) {
                switch ($column) {
                    case 'fixTime':
                        $eventData['fixTime'] = $alert->triggered_at->format('Y-m-d H:i:s');
                        break;
                    case 'type':
                        $eventData['type'] = $alert->type_text;
                        break;
                    case 'data':
                        $eventData['data'] = $alert->message;
                        break;
                    case 'geofence':
                        $eventData['geofence'] = $alert->geofence->name ?? 'N/A';
                        break;
                }
            }
            return $eventData;
        });

        return response()->json($events);
    }

    public function trips(Request $request)
    {
        // Trips report implementation
        return response()->json(['message' => 'Trips report to be implemented']);
    }

    public function stops(Request $request)
    {
        // Stops report implementation
        return response()->json(['message' => 'Stops report to be implemented']);
    }

    public function summary(Request $request)
    {
        // Summary report implementation
        return response()->json(['message' => 'Summary report to be implemented']);
    }

    public function chart(Request $request)
    {
        // Chart report implementation
        return response()->json(['message' => 'Chart report to be implemented']);
    }

    public function replay(Request $request)
    {
        // Replay report implementation
        return response()->json(['message' => 'Replay report to be implemented']);
    }
} 