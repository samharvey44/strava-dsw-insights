<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gears', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->date('first_used')->nullable();
            $table->date('decommissioned')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gears');
    }
};
