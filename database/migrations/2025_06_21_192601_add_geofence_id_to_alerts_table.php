<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->unsignedBigInteger('geofence_id')->nullable()->after('device_id');
            $table->foreign('geofence_id')->references('id')->on('geofences')->onDelete('cascade');
            $table->index(['geofence_id', 'triggered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropForeign(['geofence_id']);
            $table->dropIndex(['geofence_id', 'triggered_at']);
            $table->dropColumn('geofence_id');
        });
    }
};
