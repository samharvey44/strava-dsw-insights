<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsw_type_groups', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('display_class');
            $table->boolean('has_intervals');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsw_type_groups');
    }
};
