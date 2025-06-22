<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userSettings = Setting::getUserSettings($user->id);
        $systemSettings = $user->isAdmin() ? Setting::getSystemSettings() : [];

        return view('settings.index', compact('userSettings', 'systemSettings'));
    }

    public function updateUserSettings(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'timezone' => 'required|string',
            'units' => 'required|in:km,miles',
            'speed_limit' => 'required|integer|min:1|max:200',
            'alert_preferences' => 'array',
            'alert_preferences.*' => 'boolean',
        ]);

        $user->update([
            'timezone' => $request->timezone,
            'units' => $request->units,
            'speed_limit' => $request->speed_limit,
            'alert_preferences' => $request->alert_preferences,
        ]);

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully!');
    }

    public function updateSystemSettings(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return redirect()->route('settings.index')->with('error', 'Access denied.');
        }

        $request->validate([
            'system_name' => 'required|string|max:255',
            'api_rate_limit' => 'required|integer|min:1|max:1000',
            'position_retention_days' => 'required|integer|min:1|max:365',
            'alert_retention_days' => 'required|integer|min:1|max:365',
            'enable_email_notifications' => 'boolean',
            'enable_push_notifications' => 'boolean',
        ]);

        Setting::setValue('system_name', $request->system_name);
        Setting::setValue('api_rate_limit', $request->api_rate_limit);
        Setting::setValue('position_retention_days', $request->position_retention_days);
        Setting::setValue('alert_retention_days', $request->alert_retention_days);
        Setting::setValue('enable_email_notifications', $request->boolean('enable_email_notifications'));
        Setting::setValue('enable_push_notifications', $request->boolean('enable_push_notifications'));

        return redirect()->route('settings.index')->with('success', 'System settings updated successfully!');
    }

    public function generateApiKey(Request $request)
    {
        $user = Auth::user();
        $deviceId = $request->input('device_id');
        
        $device = $user->isAdmin() 
            ? Device::find($deviceId)
            : $user->devices()->find($deviceId);

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $apiKey = $device->generateApiKey();

        return response()->json(['api_key' => $apiKey]);
    }

    public function rotateApiKeys(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $devices = $user->isAdmin() ? Device::all() : $user->devices;
        
        foreach ($devices as $device) {
            $device->generateApiKey();
        }

        return response()->json(['message' => 'API keys rotated successfully']);
    }

    public function getSystemInfo()
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $info = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_driver' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'timezone' => config('app.timezone'),
            'debug_mode' => config('app.debug'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'storage_path' => storage_path(),
            'log_path' => storage_path('logs'),
        ];

        return response()->json($info);
    }

    public function clearCache(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Access denied.'], 403);
        }

        $type = $request->input('type', 'all');

        switch ($type) {
            case 'cache':
                \Artisan::call('cache:clear');
                break;
            case 'config':
                \Artisan::call('config:clear');
                break;
            case 'route':
                \Artisan::call('route:clear');
                break;
            case 'view':
                \Artisan::call('view:clear');
                break;
            case 'all':
                \Artisan::call('cache:clear');
                \Artisan::call('config:clear');
                \Artisan::call('route:clear');
                \Artisan::call('view:clear');
                break;
        }

        return response()->json(['message' => ucfirst($type) . ' cache cleared successfully']);
    }

    public function getTimezones()
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $formatted = [];

        foreach ($timezones as $timezone) {
            $formatted[] = [
                'value' => $timezone,
                'label' => $timezone . ' (' . (new \DateTime('now', new \DateTimeZone($timezone)))->format('P') . ')'
            ];
        }

        return response()->json($formatted);
    }

    public function exportSettings()
    {
        $user = Auth::user();
        $settings = [
            'user' => [
                'timezone' => $user->timezone,
                'units' => $user->units,
                'speed_limit' => $user->speed_limit,
                'alert_preferences' => $user->alert_preferences,
            ],
            'system' => $user->isAdmin() ? Setting::getSystemSettings() : [],
        ];

        return response()->json($settings)
            ->header('Content-Disposition', 'attachment; filename="settings.json"');
    }

    public function importSettings(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:1024',
        ]);

        $content = file_get_contents($request->file('settings_file')->getPathname());
        $settings = json_decode($content, true);

        if (isset($settings['user'])) {
            $user->update($settings['user']);
        }

        if ($user->isAdmin() && isset($settings['system'])) {
            foreach ($settings['system'] as $key => $value) {
                Setting::setValue($key, $value);
            }
        }

        return redirect()->route('settings.index')->with('success', 'Settings imported successfully!');
    }
} 