<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strava_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('strava_raw_activity_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('is_summary');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('distance_meters', 10);
            $table->unsignedInteger('moving_time_seconds');
            $table->unsignedInteger('elapsed_time_seconds');
            $table->decimal('elevation_gain_meters', 10);
            $table->dateTime('started_at')->index();
            $table->string('timezone');
            $table->text('summary_polyline')->nullable();
            $table->decimal('average_speed_meters_per_second', 10);
            $table->decimal('max_speed_meters_per_second', 10);
            $table->decimal('average_heartrate', 10)->nullable();
            $table->decimal('max_heartrate', 10)->nullable();
            $table->decimal('average_watts', 10)->nullable();
            $table->decimal('max_watts', 10)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strava_activities');
    }
};
