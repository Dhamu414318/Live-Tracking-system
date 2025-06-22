<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->decimal('start_lat', 10, 8);
            $table->decimal('start_lng', 11, 8);
            $table->decimal('end_lat', 10, 8)->nullable();
            $table->decimal('end_lng', 11, 8)->nullable();
            $table->decimal('distance', 10, 2)->default(0);
            $table->decimal('max_speed', 8, 2)->nullable();
            $table->decimal('avg_speed', 8, 2)->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->json('route_data')->nullable();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->index(['device_id', 'start_time']);
            $table->index(['device_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('trips');
    }
}; 