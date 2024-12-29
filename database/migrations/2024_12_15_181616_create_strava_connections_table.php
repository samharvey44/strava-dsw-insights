<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strava_connections', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('athlete_id');

            $table->text('access_token');
            $table->unsignedInteger('access_token_expiry');
            $table->text('refresh_token');

            $table->boolean('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strava_connections');
    }
};
