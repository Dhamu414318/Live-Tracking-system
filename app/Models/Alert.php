<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'geofence_id',
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'triggered_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    /**
     * Get the device that triggered the alert
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the geofence related to this alert
     */
    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }

    /**
     * Get the user that owns the alert
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get alert type text
     */
    public function getTypeTextAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->type));
    }

    /**
     * Get alert icon class
     */
    public function getIconClassAttribute(): string
    {
        return match($this->type) {
            'speed_limit_exceeded' => 'fas fa-tachometer-alt text-danger',
            'ignition_on' => 'fas fa-power-off text-success',
            'ignition_off' => 'fas fa-power-off text-warning',
            'geofence_enter' => 'fas fa-map-marker-alt text-info',
            'geofence_exit' => 'fas fa-map-marker-alt text-warning',
            'device_offline' => 'fas fa-wifi text-danger',
            'battery_low' => 'fas fa-battery-quarter text-warning',
            'maintenance_due' => 'fas fa-tools text-info',
            default => 'fas fa-bell text-primary',
        };
    }

    /**
     * Get alert color class
     */
    public function getColorClassAttribute(): string
    {
        return match($this->type) {
            'speed_limit_exceeded', 'device_offline' => 'danger',
            'ignition_off', 'geofence_exit', 'battery_low' => 'warning',
            'ignition_on', 'geofence_enter', 'maintenance_due' => 'info',
            default => 'primary',
        };
    }

    /**
     * Scope to get unread alerts
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get alerts by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get recent alerts
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }

    /**
     * Mark alert as read
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Mark alert as unread
     */
    public function markAsUnread(): void
    {
        $this->update(['is_read' => false]);
    }
} 