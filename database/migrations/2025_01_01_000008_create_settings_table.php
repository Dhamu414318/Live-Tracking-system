<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('key');
            $table->json('value');
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'key']);
            $table->index(['key', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
}; 