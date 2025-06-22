<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $alerts = Alert::with(['device', 'geofence'])->latest()->paginate(50);
        $devices = Device::all();
        $todayAlerts = Alert::whereDate('created_at', today())->count();
        $weekAlerts = Alert::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        
        return view('alerts.index', compact('alerts', 'devices', 'todayAlerts', 'weekAlerts'));
    }

    public function show(Alert $alert)
    {
        $alert->load(['device', 'geofence']);
        return view('alerts.show', compact('alert'));
    }

    public function markAsRead(Alert $alert)
    {
        $alert->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        Alert::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();
        return response()->json(['success' => true]);
    }

    public function clearAll()
    {
        Alert::truncate();
        return response()->json(['success' => true]);
    }

    public function create(Request $request)
    {
        $devices = Device::all();
        return view('alerts.create', compact('devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'message' => 'required|string',
            'device_id' => 'required|exists:devices,id',
            'geofence_id' => 'nullable|exists:geofences,id',
            'severity' => 'required|in:low,medium,high,critical',
        ]);

        Alert::create([
            'type' => $request->type,
            'title' => $request->type,
            'message' => $request->message,
            'device_id' => $request->device_id,
            'geofence_id' => $request->geofence_id,
            'user_id' => Auth::id(),
            'is_read' => false,
            'triggered_at' => now(),
        ]);

        return redirect()->route('alerts.index')->with('success', 'Alert created successfully.');
    }
} 