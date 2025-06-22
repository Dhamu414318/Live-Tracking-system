<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'timezone',
        'units',
        'speed_limit',
        'alert_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'alert_preferences' => 'array',
    ];

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get devices owned by this user
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Get geofences owned by this user
     */
    public function geofences(): HasMany
    {
        return $this->hasMany(Geofence::class);
    }

    /**
     * Get alerts for this user
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get all devices (for admin users)
     */
    public function getAllDevices()
    {
        if ($this->isAdmin()) {
            return Device::with('user')->get();
        }
        return $this->devices;
    }

    public function trips(): HasMany
    {
        return $this->hasManyThrough(Trip::class, Device::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function getSpeedLimitAttribute($value): int
    {
        return $value ?? 80; // Default 80 km/h
    }

    public function getUnitsAttribute($value): string
    {
        return $value ?? 'km'; // Default kilometers
    }

    public function getTimezoneAttribute($value): string
    {
        return $value ?? 'UTC';
    }

    public function getAlertPreferencesAttribute($value): array
    {
        $defaults = [
            'speed_limit_exceeded' => true,
            'ignition_on' => true,
            'ignition_off' => true,
            'battery_low' => true,
            'device_offline' => true,
            'geofence_enter' => true,
            'geofence_exit' => true,
        ];

        return array_merge($defaults, json_decode($value, true) ?? []);
    }

    public function hasDeviceAccess(Device $device): bool
    {
        return $this->isAdmin() || $this->devices->contains($device->id);
    }

    public function getUnreadAlertsCount(): int
    {
        return $this->alerts()->where('is_read', false)->count();
    }

    public function getOnlineDevicesCount(): int
    {
        return $this->devices()->where('status', 'online')->count();
    }

    public function getMovingDevicesCount(): int
    {
        return $this->devices()->where('last_speed', '>', 0)->count();
    }
}
