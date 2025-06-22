<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('unique_id')->unique();
            $table->string('model')->nullable();
            $table->enum('status', ['online', 'offline', 'maintenance'])->default('offline');
            $table->decimal('last_lat', 10, 8)->nullable();
            $table->decimal('last_lng', 11, 8)->nullable();
            $table->decimal('last_speed', 8, 2)->nullable();
            $table->boolean('ignition')->default(false);
            $table->decimal('battery_level', 5, 2)->nullable();
            $table->timestamp('last_update_time')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('devices');
    }
}; 