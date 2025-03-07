<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gears', function (Blueprint $table) {
            $table->boolean('auto_attach_to_activities')->default(false)->after('decommissioned');
        });
    }

    public function down(): void
    {
        Schema::table('gears', function (Blueprint $table) {
            $table->dropColumn('auto_attach_to_activities');
        });
    }
};
