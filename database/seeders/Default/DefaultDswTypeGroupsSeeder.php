<?php

namespace Database\Seeders\Default;

use App\Models\DswTypeGroup;
use Illuminate\Database\Seeder;

class DefaultDswTypeGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Recovery',
                'display_class' => 'primary',
                'has_intervals' => false,
            ],
            [
                'name' => 'Base',
                'display_class' => 'primary',
                'has_intervals' => false,
            ],
            [
                'name' => 'Tempo',
                'display_class' => 'warning',
                'has_intervals' => true,
            ],
            [
                'name' => 'Threshold',
                'display_class' => 'warning',
                'has_intervals' => true,
            ],
            [
                'name' => 'VO2 Max',
                'display_class' => 'warning',
                'has_intervals' => true,
            ],
            [
                'name' => 'Anaerobic',
                'display_class' => 'info',
                'has_intervals' => true,
            ],
            [
                'name' => 'Sprint',
                'display_class' => 'info',
                'has_intervals' => true,
            ],
        ];

        foreach ($types as $type) {
            DswTypeGroup::create($type);
        }
    }
}
