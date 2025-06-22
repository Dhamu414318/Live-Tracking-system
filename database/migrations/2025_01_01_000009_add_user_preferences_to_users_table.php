<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('role');
            $table->string('units')->default('km')->after('timezone');
            $table->integer('speed_limit')->default(80)->after('units');
            $table->json('alert_preferences')->nullable()->after('speed_limit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'units', 'speed_limit', 'alert_preferences']);
        });
    }
}; 