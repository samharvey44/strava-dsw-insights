<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strava_webhook_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('strava_subscription_id')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strava_webhook_subscriptions');
    }
};
