<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'start_time',
        'end_time',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'distance',
        'max_speed',
        'avg_speed',
        'duration_minutes',
        'status',
        'route_data',
    ];

    protected $casts = [
        'start_lat' => 'decimal:8',
        'start_lng' => 'decimal:8',
        'end_lat' => 'decimal:8',
        'end_lng' => 'decimal:8',
        'distance' => 'decimal:2',
        'max_speed' => 'decimal:2',
        'avg_speed' => 'decimal:2',
        'duration_minutes' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'route_data' => 'array',
    ];

    /**
     * Get the device that owns the trip
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get formatted distance
     */
    public function getFormattedDistanceAttribute(): string
    {
        if ($this->distance < 1) {
            return number_format($this->distance * 1000, 0) . ' m';
        }
        return number_format($this->distance, 2) . ' km';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Get formatted max speed
     */
    public function getFormattedMaxSpeedAttribute(): string
    {
        if ($this->max_speed === null) {
            return 'N/A';
        }
        return number_format($this->max_speed, 1) . ' km/h';
    }

    /**
     * Get formatted average speed
     */
    public function getFormattedAvgSpeedAttribute(): string
    {
        if ($this->avg_speed === null) {
            return 'N/A';
        }
        return number_format($this->avg_speed, 1) . ' km/h';
    }

    /**
     * Get formatted start location
     */
    public function getFormattedStartLocationAttribute(): string
    {
        return number_format($this->start_lat, 6) . ', ' . number_format($this->start_lng, 6);
    }

    /**
     * Get formatted end location
     */
    public function getFormattedEndLocationAttribute(): string
    {
        if ($this->end_lat && $this->end_lng) {
            return number_format($this->end_lat, 6) . ', ' . number_format($this->end_lng, 6);
        }
        return 'N/A';
    }

    /**
     * Scope to get completed trips
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get active trips
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get trips within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    /**
     * Calculate trip statistics
     */
    public function calculateStats(): void
    {
        if ($this->end_time && $this->start_time) {
            $this->duration_minutes = $this->end_time->diffInMinutes($this->start_time);
        }

        if ($this->distance > 0 && $this->duration_minutes > 0) {
            $this->avg_speed = ($this->distance / $this->duration_minutes) * 60; // km/h
        }
    }

    /**
     * Complete the trip
     */
    public function complete($endLat, $endLng): void
    {
        $this->update([
            'end_time' => now(),
            'end_lat' => $endLat,
            'end_lng' => $endLng,
            'status' => 'completed',
        ]);

        $this->calculateStats();
        $this->save();
    }

    /**
     * Get the positions for this trip
     */
    public function positions()
    {
        return $this->hasMany(Position::class, 'device_id', 'device_id')
            ->where('timestamp', '>=', $this->start_time)
            ->when($this->end_time, function($query) {
                return $query->where('timestamp', '<=', $this->end_time);
            })
            ->orderBy('timestamp');
    }

    /**
     * Get all positions for this trip (simplified for eager loading)
     */
    public function tripPositions()
    {
        return $this->hasMany(Position::class, 'device_id', 'device_id')
            ->where('timestamp', '>=', $this->start_time)
            ->when($this->end_time, function($query) {
                return $query->where('timestamp', '<=', $this->end_time);
            })
            ->orderBy('timestamp');
    }
} 