<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', [
                'speed_limit_exceeded',
                'ignition_on',
                'ignition_off',
                'geofence_enter',
                'geofence_exit',
                'device_offline',
                'battery_low',
                'maintenance_due'
            ]);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('triggered_at');
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
            $table->index(['device_id', 'triggered_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('alerts');
    }
}; 