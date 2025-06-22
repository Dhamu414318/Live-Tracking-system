<?php

namespace App\Http\Controllers;

use App\Models\Geofence;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeofenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $geofences = Geofence::with(['devices', 'alerts'])->get();
        $todayAlerts = \App\Models\Alert::whereDate('created_at', today())->count();
        $devicesInside = Device::where('status', 'online')->count(); // Simplified
        
        return view('geofences.index', compact('geofences', 'todayAlerts', 'devicesInside'));
    }

    public function create()
    {
        $devices = Device::all();
        return view('geofences.create', compact('devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:polygon,circle',
            'coordinates' => 'required|array',
            'active' => 'boolean',
            'devices' => 'array',
            'devices.*' => 'exists:devices,id',
        ]);

        $geofence = Geofence::create([
            'name' => $request->name,
            'description' => $request->description,
            'area_type' => $request->type,
            'coordinates' => $request->coordinates,
            'is_active' => $request->has('active'),
            'user_id' => Auth::id(),
        ]);

        if ($request->has('devices')) {
            $geofence->devices()->attach($request->devices);
        }

        return redirect()->route('geofences.index')->with('success', 'Geofence created successfully.');
    }

    public function show(Geofence $geofence)
    {
        $geofence->load(['devices', 'alerts' => function($query) {
            $query->latest()->take(50);
        }]);
        
        return view('geofences.show', compact('geofence'));
    }

    public function edit(Geofence $geofence)
    {
        $devices = Device::all();
        return view('geofences.edit', compact('geofence', 'devices'));
    }

    public function update(Request $request, Geofence $geofence)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:polygon,circle',
            'coordinates' => 'required|array',
            'active' => 'boolean',
            'devices' => 'array',
            'devices.*' => 'exists:devices,id',
        ]);

        $geofence->update([
            'name' => $request->name,
            'description' => $request->description,
            'area_type' => $request->type,
            'coordinates' => $request->coordinates,
            'is_active' => $request->has('active'),
        ]);

        $geofence->devices()->sync($request->devices ?? []);

        return redirect()->route('geofences.index')->with('success', 'Geofence updated successfully.');
    }

    public function destroy(Geofence $geofence)
    {
        $geofence->delete();
        return redirect()->route('geofences.index')->with('success', 'Geofence deleted successfully.');
    }

    public function map(Geofence $geofence)
    {
        $geofence->load('devices');
        return view('geofences.map', compact('geofence'));
    }

    public function toggle(Geofence $geofence)
    {
        $geofence->update(['is_active' => !$geofence->is_active]);
        return redirect()->back()->with('success', 'Geofence status updated.');
    }
} 