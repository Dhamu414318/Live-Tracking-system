<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getValue(string $key, $default = null, ?int $userId = null)
    {
        $query = static::where('key', $key);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }
        
        $setting = $query->first();
        
        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, $value, ?int $userId = null): void
    {
        $query = static::where('key', $key);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }
        
        $setting = $query->first();
        
        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            static::create([
                'user_id' => $userId,
                'key' => $key,
                'value' => $value,
                'type' => gettype($value),
            ]);
        }
    }

    public static function getSystemSettings(): array
    {
        return static::whereNull('user_id')
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function getUserSettings(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('value', 'key')
            ->toArray();
    }
} 