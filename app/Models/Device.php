<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'unique_id',
        'model',
        'status',
        'last_lat',
        'last_lng',
        'last_speed',
        'ignition',
        'battery_level',
        'last_update_time',
        'api_key',
        'is_active',
    ];

    protected $casts = [
        'last_lat' => 'float',
        'last_lng' => 'float',
        'last_speed' => 'float',
        'ignition' => 'boolean',
        'battery_level' => 'integer',
        'last_update_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the device
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get positions for this device
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Get trips for this device
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get alerts for this device
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get geofences assigned to this device
     */
    public function geofences(): BelongsToMany
    {
        return $this->belongsToMany(Geofence::class, 'device_geofence');
    }

    /**
     * Get the latest position
     */
    public function latestPosition(): HasMany
    {
        return $this->hasMany(Position::class)->latest('timestamp');
    }

    /**
     * Get active trip
     */
    public function activeTrip(): HasMany
    {
        return $this->hasMany(Trip::class)->where('status', 'active');
    }

    /**
     * Check if device is online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Check if device is offline
     */
    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    /**
     * Check if device is moving
     */
    public function isMoving(): bool
    {
        return $this->last_speed > 0;
    }

    /**
     * Check if device is idle
     */
    public function isIdle(): bool
    {
        return $this->last_speed == 0 && $this->ignition;
    }

    /**
     * Get formatted status
     */
    public function getStatusTextAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get formatted status color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'online' => 'green',
            'offline' => 'red',
            'maintenance' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Get last update ago
     */
    public function getLastUpdateAgo(): string
    {
        if (!$this->last_update_time) {
            return 'Never';
        }
        return $this->last_update_time->diffForHumans();
    }

    /**
     * Check if device is stale
     */
    public function isStale(): bool
    {
        if (!$this->last_update_time) {
            return true;
        }
        return $this->last_update_time->diffInMinutes(now()) > 15;
    }

    /**
     * Update device position
     */
    public function updatePosition(array $data): void
    {
        // Create new position record
        $position = $this->positions()->create([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'speed' => $data['speed'] ?? 0,
            'altitude' => $data['altitude'] ?? 0,
            'course' => $data['course'] ?? 0,
            'ignition' => $data['ignition'] ?? false,
            'battery_level' => $data['battery_level'] ?? null,
            'timestamp' => $data['timestamp'] ?? now(),
        ]);

        // Update device with latest position
        $this->update([
            'status' => 'online',
            'last_lat' => $position->latitude,
            'last_lng' => $position->longitude,
            'last_speed' => $position->speed,
            'ignition' => $position->ignition,
            'battery_level' => $position->battery_level,
            'last_update_time' => $position->timestamp,
        ]);

        // Check for alerts
        $this->checkAlerts($position);
    }

    /**
     * Check alerts for the device
     */
    public function checkAlerts(Position $position): void
    {
        $user = $this->user;
        $preferences = $user->alert_preferences;

        // Speed limit alert
        if ($preferences['speed_limit_exceeded'] && $position->speed > $user->speed_limit) {
            $this->createAlert('speed_limit_exceeded', 'Speed Limit Exceeded', 
                "Device {$this->name} exceeded speed limit of {$user->speed_limit} km/h");
        }

        // Ignition alerts
        if ($preferences['ignition_on'] && $position->ignition && !$this->ignition) {
            $this->createAlert('ignition_on', 'Ignition Turned ON', 
                "Device {$this->name} ignition turned ON");
        }

        if ($preferences['ignition_off'] && !$position->ignition && $this->ignition) {
            $this->createAlert('ignition_off', 'Ignition Turned OFF', 
                "Device {$this->name} ignition turned OFF");
        }

        // Battery low alert
        if ($preferences['battery_low'] && $position->battery_level && $position->battery_level < 20) {
            $this->createAlert('battery_low', 'Low Battery', 
                "Device {$this->name} battery level is {$position->battery_level}%");
        }

        // Geofence alerts
        $this->checkGeofenceAlerts($position);
    }

    /**
     * Check geofence alerts for the device
     */
    public function checkGeofenceAlerts(Position $position): void
    {
        $user = $this->user;
        $preferences = $user->alert_preferences;

        foreach ($this->geofences as $geofence) {
            if (!$geofence->is_active) continue;

            $isInside = $geofence->isPointInside($position->latitude, $position->longitude);
            $wasInside = $geofence->wasDeviceInside($this->id);

            if ($isInside && !$wasInside && $preferences['geofence_enter']) {
                $this->createAlert('geofence_enter', 'Entered Geofence', 
                    "Device {$this->name} entered geofence {$geofence->name}");
                $geofence->markDeviceInside($this->id);
            }

            if (!$isInside && $wasInside && $preferences['geofence_exit']) {
                $this->createAlert('geofence_exit', 'Exited Geofence', 
                    "Device {$this->name} exited geofence {$geofence->name}");
                $geofence->markDeviceOutside($this->id);
            }
        }
    }

    /**
     * Create a new alert for the device
     */
    public function createAlert(string $type, string $title, string $message): void
    {
        $this->alerts()->create([
            'user_id' => $this->user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'triggered_at' => now(),
        ]);
    }

    /**
     * Generate a new API key for the device
     */
    public function generateApiKey(): string
    {
        $apiKey = 'gps_' . bin2hex(random_bytes(16));
        $this->update(['api_key' => $apiKey]);
        return $apiKey;
    }

    /**
     * Get distance traveled today
     */
    public function getDistanceToday(): float
    {
        return $this->trips()
            ->whereDate('start_time', today())
            ->sum('distance');
    }

    /**
     * Get average speed today
     */
    public function getAverageSpeedToday(): float
    {
        $trips = $this->trips()->whereDate('start_time', today())->get();
        if ($trips->isEmpty()) return 0;
        
        return $trips->avg('avg_speed');
    }

    /**
     * Get uptime percentage
     */
    public function getUptimePercentage(): float
    {
        $totalMinutes = now()->diffInMinutes($this->created_at);
        $onlineMinutes = $this->positions()->count() * 5; // Assuming 5-minute intervals
        return $totalMinutes > 0 ? ($onlineMinutes / $totalMinutes) * 100 : 0;
    }
} 