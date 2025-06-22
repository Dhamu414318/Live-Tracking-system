<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Device;
use App\Models\Position;
use App\Models\Geofence;
use App\Models\Alert;
use App\Models\Trip;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'timezone' => 'UTC',
            'units' => 'km',
            'speed_limit' => 80,
            'alert_preferences' => [
                'speed_limit_exceeded' => true,
                'ignition_on' => true,
                'ignition_off' => true,
                'battery_low' => true,
                'device_offline' => true,
                'geofence_enter' => true,
                'geofence_exit' => true,
            ],
        ]);

        // Create regular users
        $users = User::factory(5)->create([
            'role' => 'user',
            'timezone' => 'UTC',
            'units' => 'km',
            'speed_limit' => 80,
            'alert_preferences' => [
                'speed_limit_exceeded' => true,
                'ignition_on' => true,
                'ignition_off' => true,
                'battery_low' => true,
                'device_offline' => true,
                'geofence_enter' => true,
                'geofence_exit' => true,
            ],
        ]);

        // Create devices for each user
        foreach ($users as $user) {
            $devices = Device::factory(rand(2, 4))->create([
                'user_id' => $user->id,
                'is_active' => true,
            ]);

            // Generate API keys for devices
            foreach ($devices as $device) {
                $device->generateApiKey();
            }

            // Create positions for each device
            foreach ($devices as $device) {
                $this->createSamplePositions($device);
                $this->createSampleTrips($device);
                $this->createSampleAlerts($device);
            }

            // Create geofences for each user
            $this->createSampleGeofences($user);
        }

        // Create some sample positions for devices
        $this->createSamplePositionsForAllDevices();

        // Create system settings
        $this->createSystemSettings();
    }

    private function createSamplePositions($device)
    {
        $baseLat = 40.7128; // New York coordinates
        $baseLng = -74.0060;
        
        // Create positions for the last 7 days
        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day);
            
            // Create 24 positions per day (every hour)
            for ($hour = 0; $hour < 24; $hour++) {
                $timestamp = $date->copy()->addHours($hour);
                
                // Simulate movement
                $lat = $baseLat + (rand(-100, 100) / 1000);
                $lng = $baseLng + (rand(-100, 100) / 1000);
                
                // Simulate realistic speed patterns
                $speed = 0;
                if ($hour >= 7 && $hour <= 9) {
                    // Morning rush hour
                    $speed = rand(20, 60);
                } elseif ($hour >= 17 && $hour <= 19) {
                    // Evening rush hour
                    $speed = rand(15, 50);
                } elseif ($hour >= 10 && $hour <= 16) {
                    // Daytime
                    $speed = rand(0, 40);
                } else {
                    // Night time
                    $speed = rand(0, 20);
                }
                
                Position::create([
                    'device_id' => $device->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'speed' => $speed,
                    'altitude' => rand(0, 1000),
                    'course' => rand(0, 360),
                    'ignition' => $speed > 0 ? true : rand(0, 1),
                    'battery_level' => rand(20, 100),
                    'timestamp' => $timestamp,
                ]);
            }
        }

        // Update device with latest position
        $latestPosition = $device->positions()->latest('timestamp')->first();
        if ($latestPosition) {
            $device->update([
                'status' => 'online',
                'last_lat' => $latestPosition->latitude,
                'last_lng' => $latestPosition->longitude,
                'last_speed' => $latestPosition->speed,
                'ignition' => $latestPosition->ignition,
                'battery_level' => $latestPosition->battery_level,
                'last_update_time' => $latestPosition->timestamp,
            ]);
        }
    }

    private function createSampleTrips($device)
    {
        // Create trips for the last 7 days
        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day);
            
            // Create 2-4 trips per day
            $numTrips = rand(2, 4);
            
            for ($trip = 0; $trip < $numTrips; $trip++) {
                $startHour = rand(6, 20);
                $startTime = $date->copy()->addHours($startHour)->addMinutes(rand(0, 59));
                $duration = rand(30, 180); // 30 minutes to 3 hours
                $endTime = $startTime->copy()->addMinutes($duration);
                
                $startLat = 40.7128 + (rand(-100, 100) / 1000);
                $startLng = -74.0060 + (rand(-100, 100) / 1000);
                $endLat = $startLat + (rand(-50, 50) / 1000);
                $endLng = $startLng + (rand(-50, 50) / 1000);
                
                $distance = rand(5, 50);
                $maxSpeed = rand(60, 120);
                $avgSpeed = rand(30, 80);
                
                Trip::create([
                    'device_id' => $device->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'start_lat' => $startLat,
                    'start_lng' => $startLng,
                    'end_lat' => $endLat,
                    'end_lng' => $endLng,
                    'distance' => $distance,
                    'max_speed' => $maxSpeed,
                    'avg_speed' => $avgSpeed,
                    'duration_minutes' => $duration,
                    'status' => 'completed',
                ]);
            }
        }
    }

    private function createSampleAlerts($device)
    {
        $alertTypes = [
            'speed_limit_exceeded' => 'Speed Limit Exceeded',
            'ignition_on' => 'Ignition Turned ON',
            'ignition_off' => 'Ignition Turned OFF',
            'battery_low' => 'Low Battery',
            'device_offline' => 'Device Offline',
            'geofence_enter' => 'Entered Geofence',
            'geofence_exit' => 'Exited Geofence',
        ];

        // Create alerts for the last 7 days
        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $numAlerts = rand(1, 5);
            
            for ($alert = 0; $alert < $numAlerts; $alert++) {
                $type = array_rand($alertTypes);
                $alertTime = $date->copy()->addHours(rand(0, 23))->addMinutes(rand(0, 59));
                
                Alert::create([
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'type' => $type,
                    'title' => $alertTypes[$type],
                    'message' => "Device {$device->name}: {$alertTypes[$type]}",
                    'is_read' => rand(0, 1),
                    'triggered_at' => $alertTime,
                ]);
            }
        }
    }

    private function createSampleGeofences($user)
    {
        // Create office geofence
        Geofence::create([
            'user_id' => $user->id,
            'name' => 'Office Area',
            'description' => 'Main office building and parking area',
            'area_type' => 'circle',
            'coordinates' => [
                'center' => [
                    'lat' => 40.7128,
                    'lng' => -74.0060,
                ],
                'radius' => 500, // 500 meters
            ],
            'color' => '#FF0000',
            'is_active' => true,
        ]);

        // Create downtown geofence
        Geofence::create([
            'user_id' => $user->id,
            'name' => 'Downtown Zone',
            'description' => 'Downtown business district',
            'area_type' => 'polygon',
            'coordinates' => [
                'polygon' => [
                    ['lat' => 40.7128, 'lng' => -74.0060],
                    ['lat' => 40.7228, 'lng' => -74.0060],
                    ['lat' => 40.7228, 'lng' => -73.9960],
                    ['lat' => 40.7128, 'lng' => -73.9960],
                ],
            ],
            'color' => '#00FF00',
            'is_active' => true,
        ]);

        // Create warehouse geofence
        Geofence::create([
            'user_id' => $user->id,
            'name' => 'Warehouse',
            'description' => 'Main warehouse facility',
            'area_type' => 'circle',
            'coordinates' => [
                'center' => [
                    'lat' => 40.7028,
                    'lng' => -74.0160,
                ],
                'radius' => 300, // 300 meters
            ],
            'color' => '#0000FF',
            'is_active' => true,
        ]);
    }

    private function createSamplePositionsForAllDevices()
    {
        $devices = Device::all();
        
        foreach ($devices as $device) {
            // Create some recent positions for real-time tracking demo
            for ($i = 0; $i < 10; $i++) {
                $baseLat = 40.7128;
                $baseLng = -74.0060;
                
                $lat = $baseLat + (rand(-50, 50) / 1000);
                $lng = $baseLng + (rand(-50, 50) / 1000);
                
                Position::create([
                    'device_id' => $device->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'speed' => rand(0, 80),
                    'altitude' => rand(0, 1000),
                    'course' => rand(0, 360),
                    'ignition' => rand(0, 1),
                    'battery_level' => rand(20, 100),
                    'timestamp' => now()->subMinutes($i * 5),
                ]);
            }
        }
    }

    private function createSystemSettings()
    {
        $settings = [
            'system_name' => 'GPS Tracking System',
            'api_rate_limit' => 100,
            'position_retention_days' => 90,
            'alert_retention_days' => 365,
            'enable_email_notifications' => true,
            'enable_push_notifications' => false,
            'default_timezone' => 'UTC',
            'default_units' => 'km',
            'default_speed_limit' => 80,
        ];

        foreach ($settings as $key => $value) {
            Setting::create([
                'key' => $key,
                'value' => $value,
                'type' => gettype($value),
                'description' => ucfirst(str_replace('_', ' ', $key)),
            ]);
        }
    }
}
