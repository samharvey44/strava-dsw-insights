<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gear_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('gear_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->unsignedTinyInteger('trigger_after_number_of_activities');
            $table->unsignedTinyInteger('current_number_of_activities')->default(0);
            $table->dateTime('last_triggered')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gear_reminders');
    }
};
