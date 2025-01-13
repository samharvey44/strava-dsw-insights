<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\Default\DefaultDswTypeGroupsSeeder;
use Database\Seeders\Default\DefaultDswTypesSeeder;
use Database\Seeders\Default\DefaultUserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DefaultUserSeeder::class,
            DefaultDswTypeGroupsSeeder::class,
            DefaultDswTypesSeeder::class,
        ]);

        if (app()->isProduction()) {
            return;
        }

        // Seeders for test data...
    }
}
