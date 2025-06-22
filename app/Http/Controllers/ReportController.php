<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Trip;
use App\Models\Alert;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
            case 'this_month':
                return [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth()
                ];
            case 'previous_month':
                return [
                    'start' => Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                    'end' => Carbon::now()->subMonthNoOverflow()->endOfMonth()
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
        $deviceId = $request->get('device_id');
        $period = $request->get('period', 'this_month');
        $columns = $request->get('columns');
    
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        } elseif ($columns === null) {
            $columns = ['start_time', 'end_time', 'distance', 'average_speed'];
        }
    
        $dates = $this->getDateRange($period);
    
        $query = Trip::with('device')
            ->whereBetween('start_time', [$dates['start'], $dates['end']]);
    
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
    
        $trips = $query->latest('start_time')->get();
    
        return response()->json($trips->map(function ($trip) use ($columns) {
            $data = ['id' => $trip->id];
            foreach ($columns as $column) {
                switch ($column) {
                    case 'start_time':
                        $data['start_time'] = $trip->start_time ? $trip->start_time->format('d/m/Y, H:i A') : 'N/A';
                        break;
                    case 'end_time':
                        $data['end_time'] = $trip->end_time ? $trip->end_time->format('d/m/Y, H:i A') : 'N/A';
                        break;
                    case 'distance':
                        $data['distance'] = $trip->formatted_distance;
                        break;
                    case 'average_speed':
                        $data['average_speed'] = $trip->formatted_avg_speed;
                        break;
                    case 'max_speed':
                        $data['max_speed'] = $trip->formatted_max_speed;
                        break;
                    case 'duration':
                        $data['duration'] = $trip->formatted_duration;
                        break;
                }
            }
            return $data;
        }));
    }

    public function stops(Request $request)
    {
        $deviceId = $request->get('device_id');
        $period = $request->get('period', 'this_month');
        $columns = $request->get('columns');
        $minStopTime = 5; // Minimum stop time in minutes

        if (is_string($columns)) {
            $columns = explode(',', $columns);
        } elseif ($columns === null) {
            $columns = ['start_time', 'end_time', 'duration', 'address'];
        }

        $dates = $this->getDateRange($period);
        $query = Position::orderBy('timestamp');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        $query->whereBetween('timestamp', [$dates['start'], $dates['end']]);

        $positions = $query->get();
        $stops = [];
        $currentStop = null;

        foreach ($positions as $position) {
            if ($position->speed == 0) {
                if ($currentStop === null) {
                    $currentStop = [
                        'start_time' => $position->timestamp,
                        'end_time' => $position->timestamp,
                        'latitude' => $position->latitude,
                        'longitude' => $position->longitude,
                        'odometer' => $position->distance,
                    ];
                } else {
                    $currentStop['end_time'] = $position->timestamp;
                }
            } else {
                if ($currentStop !== null) {
                    $duration = $currentStop['start_time']->diffInMinutes($currentStop['end_time']);
                    if ($duration >= $minStopTime) {
                        $currentStop['duration'] = $duration;
                        $stops[] = $currentStop;
                    }
                    $currentStop = null;
                }
            }
        }

        if ($currentStop !== null) {
            $duration = $currentStop['start_time']->diffInMinutes($currentStop['end_time']);
            if ($duration >= $minStopTime) {
                $currentStop['duration'] = $duration;
                $stops[] = $currentStop;
            }
        }

        return response()->json(collect($stops)->map(function ($stop) use ($columns) {
            $data = [];
            foreach ($columns as $column) {
                switch ($column) {
                    case 'start_time':
                        $data['start_time'] = $stop['start_time']->format('d/m/Y, H:i A');
                        break;
                    case 'end_time':
                        $data['end_time'] = $stop['end_time']->format('d/m/Y, H:i A');
                        break;
                    case 'odometer':
                        $data['odometer'] = number_format($stop['odometer'], 2) . ' km';
                        break;
                    case 'address':
                        $data['address'] = $stop['latitude'] . ', ' . $stop['longitude'];
                        break;
                    case 'duration':
                        $data['duration'] = Carbon::now()->addMinutes($stop['duration'])->diffForHumans(null, true);
                        break;
                }
            }
            return $data;
        }));
    }

    public function summary(Request $request)
    {
        $deviceId = $request->get('device_id');
        $period = $request->get('period', 'this_month');
        $columns = $request->get('columns');

        if (is_string($columns)) {
            $columns = explode(',', $columns);
        } elseif ($columns === null) {
            $columns = ['device', 'start_date', 'distance', 'average_speed'];
        }

        $dates = $this->getDateRange($period);

        $devicesQuery = Device::query();

        if ($deviceId) {
            $devicesQuery->where('id', $deviceId);
        }

        $devices = $devicesQuery->with(['trips' => function ($query) use ($dates) {
            $query->whereBetween('start_time', [$dates['start'], $dates['end']]);
        }])->get();

        $summaryData = $devices->map(function ($device) use ($dates, $columns) {
            $totalDistance = $device->trips->sum('distance');
            $avgSpeed = $device->trips->avg('avg_speed');

            $rowData = [];
            foreach ($columns as $column) {
                switch ($column) {
                    case 'device':
                        $rowData['device'] = $device->name;
                        break;
                    case 'start_date':
                        $rowData['start_date'] = $dates['start']->format('d/m/Y');
                        break;
                    case 'distance':
                        $rowData['distance'] = number_format($totalDistance, 2) . ' km';
                        break;
                    case 'average_speed':
                        $rowData['average_speed'] = number_format($avgSpeed, 2) . ' km/h';
                        break;
                }
            }
            return $rowData;
        })->filter();

        return response()->json($summaryData->values());
    }

    public function chart(Request $request)
    {
        $deviceId = $request->get('device_id');
        $period = $request->get('period', 'week');
        $chartType = $request->get('chart_type', 'speed');

        $dates = $this->getDateRange($period);

        $query = Position::query()->select('timestamp', $chartType)
            ->whereBetween('timestamp', [$dates['start'], $dates['end']])
            ->orderBy('timestamp');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $positions = $query->get();

        $chartData = $positions->map(function ($position) use ($chartType) {
            return [
                'x' => $position->timestamp->toIso8601String(),
                'y' => $position->{$chartType},
            ];
        });

        return response()->json($chartData);
    }

    public function replay(Request $request)
    {
        $request->validate([
            'device_id' => 'required|integer|exists:devices,id',
            'period' => 'required|string',
        ]);

        $deviceId = $request->get('device_id');
        $period = $request->get('period');

        $dates = $this->getDateRange($period);

        $positions = Position::where('device_id', $deviceId)
            ->whereBetween('timestamp', [$dates['start'], $dates['end']])
            ->orderBy('timestamp', 'asc')
            ->get(['id', 'latitude', 'longitude', 'timestamp', 'speed', 'course', 'distance']);

        return response()->json($positions);
    }

    public function reverseGeocode(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $lat = $request->get('lat');
        $lon = $request->get('lon');

        // Using Nominatim for reverse geocoding.
        // See Nominatim usage policy: https://operations.osmfoundation.org/policies/nominatim/
        $response = Http::withHeaders([
            'User-Agent' => config('app.name', 'Laravel') . ' - Geocoding Request',
        ])->get("https://nominatim.openstreetmap.org/reverse", [
            'format' => 'jsonv2',
            'lat' => $lat,
            'lon' => $lon,
        ]);

        if ($response->successful()) {
            return response()->json([
                'address' => $response->json('display_name', 'Address not found.')
            ]);
        }

        return response()->json(['address' => 'Could not retrieve address.'], 500);
    }
} 