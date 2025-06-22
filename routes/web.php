<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\GeofenceController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\SettingsController;
use App\Models\User;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.post');
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register'])->name('register.post');
    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    
    // Map
    Route::get('/map', [MapController::class, 'index'])->name('map.index');
    Route::get('/api/device-positions', [MapController::class, 'getDevicePositions'])->name('api.device-positions');
    Route::get('/api/device-history', [MapController::class, 'getDeviceHistory'])->name('api.device-history');
    Route::get('/api/geofence-devices', [MapController::class, 'getGeofenceDevices'])->name('api.geofence-devices');
    Route::get('/api/map-bounds', [MapController::class, 'getMapBounds'])->name('api.map-bounds');
    Route::get('/api/export-track', [MapController::class, 'exportTrack'])->name('api.export-track');
    
    // API endpoints for live updates
    Route::get('/api/live-updates', [DashboardController::class, 'getLiveUpdates'])->name('api.live-updates');
    Route::get('/api/unread-alerts-count', [DashboardController::class, 'getUnreadAlertsCount'])->name('api.unread-alerts-count');
    
    // Devices
    Route::resource('devices', DeviceController::class);
    Route::get('/devices/{device}/positions', [DeviceController::class, 'positions'])->name('devices.positions');
    Route::get('/devices/{device}/map', [DeviceController::class, 'map'])->name('devices.map');
    Route::post('/devices/{device}/api-key', [DeviceController::class, 'apiKey'])->name('devices.api-key');
    Route::get('/devices/{device}/trips', [DeviceController::class, 'getTrips'])->name('devices.trips');
    Route::get('/devices/{device}/alerts', [DeviceController::class, 'getAlerts'])->name('devices.alerts');
    Route::post('/devices/{device}/generate-api-key', [DeviceController::class, 'generateApiKey'])->name('devices.generate-api-key');
    Route::get('/api/generate-unique-id', [DeviceController::class, 'generateUniqueId'])->name('api.generate-unique-id');
    
    // Geofences
    Route::resource('geofences', GeofenceController::class);
    Route::get('/geofences/{geofence}/map', [GeofenceController::class, 'map'])->name('geofences.map');
    Route::post('/geofences/{geofence}/toggle', [GeofenceController::class, 'toggle'])->name('geofences.toggle');
    Route::post('/geofences/{geofence}/assign-devices', [GeofenceController::class, 'assignDevices'])->name('geofences.assign-devices');
    Route::get('/api/geofences', [GeofenceController::class, 'getGeofences'])->name('api.geofences');
    
    // Alerts
    Route::resource('alerts', AlertController::class);
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markAsRead'])->name('alerts.read');
    Route::post('/alerts/mark-all-read', [AlertController::class, 'markAllRead'])->name('alerts.mark-all-read');
    Route::delete('/alerts/clear-all', [AlertController::class, 'clearAll'])->name('alerts.clear-all');
    
    // Trips 
    Route::resource('trips', TripController::class);
    Route::get('/trips/{trip}/map', [TripController::class, 'map'])->name('trips.map');
    Route::get('/trips/{trip}/export', [TripController::class, 'export'])->name('trips.export');
    Route::post('/trips/generate', [TripController::class, 'generate'])->name('trips.generate');
    Route::get('/trips/{trip}/route', [TripController::class, 'getTripRoute'])->name('trips.route');
    Route::get('/trips/filter', [TripController::class, 'filter'])->name('trips.filter');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/device/{device}', [ReportController::class, 'device'])->name('reports.device');
    Route::get('/reports/speed', [ReportController::class, 'speed'])->name('reports.speed');
    Route::get('/reports/usage', [ReportController::class, 'usage'])->name('reports.usage');
    Route::get('/reports/geofences', [ReportController::class, 'geofences'])->name('reports.geofences');
    Route::post('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/api/reports/combined', [ReportController::class, 'combined'])->name('api.reports.combined');
    Route::get('/api/reports/route', [ReportController::class, 'route'])->name('api.reports.route');
    Route::get('/api/reports/events', [ReportController::class, 'events'])->name('api.reports.events');
    
    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/user', [SettingsController::class, 'updateUserSettings'])->name('settings.user.update');
    Route::post('/settings/system', [SettingsController::class, 'updateSystemSettings'])->name('settings.system.update');
    Route::post('/settings/generate-api-key', [SettingsController::class, 'generateApiKey'])->name('settings.generate-api-key');
    Route::post('/settings/rotate-api-keys', [SettingsController::class, 'rotateApiKeys'])->name('settings.rotate-api-keys');
    Route::get('/api/system-info', [SettingsController::class, 'getSystemInfo'])->name('api.system-info');
    Route::post('/api/clear-cache', [SettingsController::class, 'clearCache'])->name('api.clear-cache');
    Route::get('/api/timezones', [SettingsController::class, 'getTimezones'])->name('api.timezones');
    Route::get('/settings/export', [SettingsController::class, 'exportSettings'])->name('settings.export');
    Route::post('/settings/import', [SettingsController::class, 'importSettings'])->name('settings.import');
    
    // Admin routes (only for admin users)
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/admin/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
        Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
        Route::get('/admin/system-status', [AdminController::class, 'systemStatus'])->name('admin.system-status');
        Route::get('/admin/logs', [AdminController::class, 'logs'])->name('admin.logs');
    });
});

// Legacy route for backward compatibility
Route::get('/home', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

// API Routes for device communication
Route::prefix('api')->group(function () {
    Route::post('/position', [App\Http\Controllers\Api\PositionController::class, 'store']);
    Route::get('/devices/{device}/positions', [App\Http\Controllers\Api\PositionController::class, 'index']);
});

 