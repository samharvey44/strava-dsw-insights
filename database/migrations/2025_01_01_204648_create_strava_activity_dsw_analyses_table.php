<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strava_activity_dsw_analyses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('strava_activity_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('dsw_type_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('intervals');
            $table->boolean('treadmill');
            $table->unsignedInteger('dsw_score');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strava_activity_dsw_analyses');
    }
};
