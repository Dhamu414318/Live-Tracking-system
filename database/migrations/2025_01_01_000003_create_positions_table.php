<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('course', 8, 2)->nullable();
            $table->boolean('ignition')->default(false);
            $table->decimal('battery_level', 5, 2)->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->index(['device_id', 'timestamp']);
            $table->index('timestamp');
        });
    }

    public function down()
    {
        Schema::dropIfExists('positions');
    }
}; 