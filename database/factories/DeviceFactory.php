<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $models = ['GT06N', 'TK103', 'VT310', 'GT02A', 'TK102'];
        
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(['Company Car', 'Delivery Van', 'Service Truck', 'Fleet Vehicle', 'Personal Vehicle']) . ' ' . $this->faker->numberBetween(1, 999),
            'unique_id' => 'DEV_' . strtoupper(Str::random(8)),
            'model' => $this->faker->randomElement($models),
            'status' => $this->faker->randomElement(['online', 'offline', 'maintenance']),
            'last_lat' => $this->faker->latitude(40.5, 40.9),
            'last_lng' => $this->faker->longitude(-74.1, -73.9),
            'last_speed' => $this->faker->randomFloat(2, 0, 120),
            'ignition' => $this->faker->boolean(),
            'battery_level' => $this->faker->randomFloat(2, 20, 100),
            'last_update_time' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * Indicate that the device is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_update_time' => now(),
        ]);
    }

    /**
     * Indicate that the device is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'last_update_time' => now()->subHours(rand(1, 24)),
        ]);
    }

    /**
     * Indicate that the device is in maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
        ]);
    }
} 