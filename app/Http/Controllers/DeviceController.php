<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $devices = Device::with('user')->get();
        $users = User::all();
        
        return view('devices.index', compact('devices', 'users'));
    }

    public function create()
    {
        $users = User::all();
        return view('devices.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unique_id' => 'required|string|unique:devices,unique_id|max:255',
            'model' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $device = Device::create([
            'name' => $request->name,
            'unique_id' => $request->unique_id,
            'model' => $request->model,
            'phone' => $request->phone,
            'contact' => $request->contact,
            'category' => $request->category,
            'user_id' => $request->user_id ?? Auth::id(),
            'api_key' => Str::random(32),
            'status' => 'offline',
        ]);

        return redirect()->route('devices.index')->with('success', 'Device created successfully.');
    }

    public function show(Device $device)
    {
        $device->load(['user', 'positions' => function($query) {
            $query->latest()->take(100);
        }]);
        
        $latestPosition = $device->positions()->latest()->first();
        $totalDistance = $device->positions()->sum('distance');
        $totalTime = $device->positions()->count() * 30; // Assuming 30-second intervals
        
        return view('devices.show', compact('device', 'latestPosition', 'totalDistance', 'totalTime'));
    }

    public function edit(Device $device)
    {
        $users = User::all();
        return view('devices.edit', compact('device', 'users'));
    }

    public function update(Request $request, Device $device)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unique_id' => 'required|string|unique:devices,unique_id,' . $device->id . '|max:255',
            'model' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'disabled' => 'boolean',
        ]);

        $device->update([
            'name' => $request->name,
            'unique_id' => $request->unique_id,
            'model' => $request->model,
            'phone' => $request->phone,
            'contact' => $request->contact,
            'category' => $request->category,
            'user_id' => $request->user_id ?? Auth::id(),
            'disabled' => $request->has('disabled'),
        ]);

        return redirect()->route('devices.index')->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Device deleted successfully.');
    }

    public function positions(Device $device)
    {
        $positions = $device->positions()->latest()->paginate(50);
        return view('devices.positions', compact('device', 'positions'));
    }

    public function map(Device $device)
    {
        $positions = $device->positions()->latest()->take(1000)->get();
        return view('devices.map', compact('device', 'positions'));
    }

    public function apiKey(Device $device)
    {
        $device->update(['api_key' => Str::random(32)]);
        return redirect()->back()->with('success', 'API key regenerated successfully.');
    }
} 