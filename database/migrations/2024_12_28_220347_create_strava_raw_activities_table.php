<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strava_raw_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('strava_connection_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('strava_activity_id');
            $table->json('data');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strava_raw_activities');
    }
};
