<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strava_activity_gear', function (Blueprint $table) {
            $table->id();

            $table->foreignId('strava_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('gear_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strava_activity_gear');
    }
};
