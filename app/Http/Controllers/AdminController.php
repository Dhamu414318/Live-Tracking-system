<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Device;
use App\Models\Trip;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $stats = [
            'total_users' => User::where('role', 'user')->count(),
            'total_devices' => Device::count(),
            'online_devices' => Device::where('status', 'online')->count(),
            'total_trips' => Trip::count(),
            'total_alerts' => Alert::count(),
            'unread_alerts' => Alert::where('is_read', false)->count(),
        ];

        $recentUsers = User::where('role', 'user')->latest()->take(5)->get();
        $recentDevices = Device::with('user')->latest()->take(5)->get();
        $recentAlerts = Alert::with('device.user')->latest('triggered_at')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentDevices', 'recentAlerts'));
    }

    public function users()
    {
        $users = User::where('role', 'user')->withCount('devices')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }

    public function editUser(User $user)
    {
        if ($user->role === 'admin') {
            abort(403);
        }

        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        if ($user->role === 'admin') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    public function destroyUser(User $user)
    {
        if ($user->role === 'admin') {
            abort(403);
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }

    public function settings()
    {
        $settings = [
            'speed_limit' => config('gps.speed_limit', 80),
            'battery_threshold' => config('gps.battery_threshold', 20),
            'offline_threshold' => config('gps.offline_threshold', 5),
            'trip_detection_speed' => config('gps.trip_detection_speed', 5),
            'trip_end_delay' => config('gps.trip_end_delay', 5),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'speed_limit' => 'required|numeric|min:1|max:200',
            'battery_threshold' => 'required|numeric|min:1|max:100',
            'offline_threshold' => 'required|numeric|min:1|max:60',
            'trip_detection_speed' => 'required|numeric|min:1|max:50',
            'trip_end_delay' => 'required|numeric|min:1|max:30',
        ]);

        // In a real application, you would save these to a settings table or config file
        // For now, we'll just return success
        return redirect()->route('admin.settings')->with('success', 'Settings updated successfully.');
    }

    public function systemStats()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('created_at', '>=', now()->subDays(30))->count(),
                'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'devices' => [
                'total' => Device::count(),
                'online' => Device::where('status', 'online')->count(),
                'offline' => Device::where('status', 'offline')->count(),
                'maintenance' => Device::where('status', 'maintenance')->count(),
            ],
            'trips' => [
                'total' => Trip::count(),
                'completed' => Trip::where('status', 'completed')->count(),
                'active' => Trip::where('status', 'active')->count(),
                'this_month' => Trip::where('start_time', '>=', now()->startOfMonth())->count(),
            ],
            'alerts' => [
                'total' => Alert::count(),
                'unread' => Alert::where('is_read', false)->count(),
                'this_month' => Alert::where('triggered_at', '>=', now()->startOfMonth())->count(),
            ],
        ];

        return response()->json($stats);
    }

    public function deviceOverview()
    {
        $devices = Device::with('user')
            ->withCount(['positions', 'trips', 'alerts'])
            ->orderBy('last_update_time', 'desc')
            ->paginate(20);

        return view('admin.devices.overview', compact('devices'));
    }

    public function alertOverview()
    {
        $alerts = Alert::with(['device.user'])
            ->latest('triggered_at')
            ->paginate(20);

        $alertTypes = Alert::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();

        return view('admin.alerts.overview', compact('alerts', 'alertTypes'));
    }
} 