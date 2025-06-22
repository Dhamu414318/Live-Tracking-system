<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Geofence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'area_type',
        'coordinates',
        'color',
        'is_active',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the geofence
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get devices assigned to this geofence
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_geofence');
    }

    /**
     * Get alerts related to this geofence
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Check if a point is inside the geofence
     */
    public function isPointInside(float $latitude, float $longitude): bool
    {
        if ($this->area_type === 'circle') {
            return $this->isPointInCircle($latitude, $longitude);
        } elseif ($this->area_type === 'polygon') {
            return $this->isPointInPolygon($latitude, $longitude);
        }
        
        return false;
    }

    /**
     * Check if point is inside circle
     */
    public function isPointInCircle(float $latitude, float $longitude): bool
    {
        $center = $this->coordinates['center'];
        $radius = $this->coordinates['radius'];
        
        $distance = $this->calculateDistance(
            $center['lat'], 
            $center['lng'], 
            $latitude, 
            $longitude
        );
        
        return $distance <= $radius;
    }

    /**
     * Check if point is inside polygon
     */
    public function isPointInPolygon(float $latitude, float $longitude): bool
    {
        $polygon = $this->coordinates['polygon'];
        $inside = false;
        $j = count($polygon) - 1;
        
        for ($i = 0; $i < count($polygon); $i++) {
            if ((($polygon[$i]['lat'] > $latitude) != ($polygon[$j]['lat'] > $latitude)) &&
                ($longitude < ($polygon[$j]['lng'] - $polygon[$i]['lng']) * ($latitude - $polygon[$i]['lat']) / 
                ($polygon[$j]['lat'] - $polygon[$i]['lat']) + $polygon[$i]['lng'])) {
                $inside = !$inside;
            }
            $j = $i;
        }
        
        return $inside;
    }

    /**
     * Calculate distance between two points in kilometers
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Get formatted coordinates for display
     */
    public function getFormattedCoordinatesAttribute(): string
    {
        if ($this->area_type === 'circle') {
            $center = $this->coordinates['center'] ?? [];
            $radius = $this->coordinates['radius'] ?? 0;
            return "Center: {$center['lat']}, {$center['lng']}, Radius: {$radius}m";
        } else {
            $polygon = $this->coordinates['polygon'] ?? [];
            return count($polygon) . " points";
        }
    }

    public function wasDeviceInside(int $deviceId): bool
    {
        $cacheKey = "geofence_{$this->id}_device_{$deviceId}_inside";
        return Cache::get($cacheKey, false);
    }

    public function markDeviceInside(int $deviceId): void
    {
        $cacheKey = "geofence_{$this->id}_device_{$deviceId}_inside";
        Cache::put($cacheKey, true, now()->addHours(24));
    }

    public function markDeviceOutside(int $deviceId): void
    {
        $cacheKey = "geofence_{$this->id}_device_{$deviceId}_inside";
        Cache::put($cacheKey, false, now()->addHours(24));
    }

    public function getDevicesInside(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->devices()->whereHas('positions', function ($query) {
            $query->whereRaw('ST_Contains(ST_GeomFromGeoJSON(?), POINT(longitude, latitude))', [
                json_encode($this->getGeoJson())
            ]);
        })->get();
    }

    public function getGeoJson(): array
    {
        if ($this->area_type === 'circle') {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$this->coordinates['center']['lng'], $this->coordinates['center']['lat']]
                ],
                'properties' => [
                    'radius' => $this->coordinates['radius']
                ]
            ];
        } elseif ($this->area_type === 'polygon') {
            $coordinates = array_map(function ($point) {
                return [$point['lng'], $point['lat']];
            }, $this->coordinates['polygon']);
            
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [$coordinates]
                ]
            ];
        }
        
        return [];
    }

    public function getArea(): float
    {
        if ($this->area_type === 'circle') {
            $radius = $this->coordinates['radius'];
            return M_PI * $radius * $radius;
        } elseif ($this->area_type === 'polygon') {
            return $this->calculatePolygonArea();
        }
        
        return 0;
    }

    public function calculatePolygonArea(): float
    {
        $polygon = $this->coordinates['polygon'];
        $area = 0;
        $j = count($polygon) - 1;
        
        for ($i = 0; $i < count($polygon); $i++) {
            $area += ($polygon[$j]['lng'] + $polygon[$i]['lng']) * 
                     ($polygon[$j]['lat'] - $polygon[$i]['lat']);
            $j = $i;
        }
        
        return abs($area) / 2;
    }

    public function getCenterPoint(): array
    {
        if ($this->area_type === 'circle') {
            return $this->coordinates['center'];
        } elseif ($this->area_type === 'polygon') {
            $polygon = $this->coordinates['polygon'];
            $latSum = 0;
            $lngSum = 0;
            
            foreach ($polygon as $point) {
                $latSum += $point['lat'];
                $lngSum += $point['lng'];
            }
            
            return [
                'lat' => $latSum / count($polygon),
                'lng' => $lngSum / count($polygon)
            ];
        }
        
        return ['lat' => 0, 'lng' => 0];
    }

    public function getVisitCount(int $deviceId, $startDate = null, $endDate = null): int
    {
        $query = $this->devices()->where('device_id', $deviceId);
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query->count();
    }

    public function getTotalVisits($startDate = null, $endDate = null): int
    {
        $query = $this->devices();
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query->count();
    }
} 