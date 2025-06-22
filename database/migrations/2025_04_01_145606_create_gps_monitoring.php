<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('user_locations')){
            Schema::create('user_locations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('battery_level', 5, 2);
                $table->json('device_info');
                $table->boolean('is_online')->default(true);
                $table->timestamp('last_update');
                $table->timestamps();
            });
        }

        if(!Schema::hasTable('attendance_logs')){
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('check_in_latitude', 10, 8);
            $table->decimal('check_in_longitude', 11, 8);
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->timestamp('check_in_time');
            $table->timestamp('check_out_time')->nullable();
            $table->string('status')->default('present');
            $table->timestamps();
        });
    }
    }

    public function down()
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('user_locations');
    }
};