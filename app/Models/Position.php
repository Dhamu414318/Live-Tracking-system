<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'speed',
        'altitude',
        'course',
        'distance',
        'ignition',
        'battery_level',
        'additional_data',
        'timestamp',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'speed' => 'decimal:2',
        'altitude' => 'decimal:2',
        'course' => 'decimal:2',
        'ignition' => 'boolean',
        'battery_level' => 'decimal:2',
        'additional_data' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the device that owns the position
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get formatted speed
     */
    public function getFormattedSpeedAttribute(): string
    {
        if ($this->speed === null) {
            return 'N/A';
        }
        return number_format($this->speed, 1) . ' km/h';
    }

    /**
     * Get formatted course
     */
    public function getFormattedCourseAttribute(): string
    {
        if ($this->course === null) {
            return 'N/A';
        }
        return number_format($this->course, 1) . '°';
    }

    /**
     * Get formatted battery level
     */
    public function getFormattedBatteryAttribute(): string
    {
        if ($this->battery_level === null) {
            return 'N/A';
        }
        return number_format($this->battery_level, 1) . '%';
    }

    /**
     * Get ignition status text
     */
    public function getIgnitionTextAttribute(): string
    {
        return $this->ignition ? 'ON' : 'OFF';
    }

    /**
     * Scope to get positions within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope to get positions for a specific device
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
} 