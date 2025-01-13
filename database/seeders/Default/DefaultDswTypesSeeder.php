<?php

namespace Database\Seeders\Default;

use App\Models\DswType;
use App\Models\DswTypeGroup;
use Illuminate\Database\Seeder;

class DefaultDswTypesSeeder extends Seeder
{
    public function run(): void
    {
        $recoveryTypeGroupId = DswTypeGroup::where('name', 'Recovery')->first()->id;
        $baseTypeGroupId = DswTypeGroup::where('name', 'Base')->first()->id;
        $tempoTypeGroupId = DswTypeGroup::where('name', 'Tempo')->first()->id;
        $thresholdTypeGroupId = DswTypeGroup::where('name', 'Threshold')->first()->id;
        $vo2MaxTypeGroupId = DswTypeGroup::where('name', 'VO2 Max')->first()->id;
        $anaerobicTypeGroupId = DswTypeGroup::where('name', 'Anaerobic')->first()->id;
        $sprintTypeGroupId = DswTypeGroup::where('name', 'Sprint')->first()->id;

        $types = [
            [
                'dsw_type_group_id' => $recoveryTypeGroupId,
                'name' => 'Recovery',
            ],
            [
                'dsw_type_group_id' => $baseTypeGroupId,
                'name' => 'Base',
            ],
            [
                'dsw_type_group_id' => $baseTypeGroupId,
                'name' => 'Long Run',
            ],
            [
                'dsw_type_group_id' => $tempoTypeGroupId,
                'name' => 'Tempo',
            ],
            [
                'dsw_type_group_id' => $thresholdTypeGroupId,
                'name' => 'Threshold',
            ],
            [
                'dsw_type_group_id' => $vo2MaxTypeGroupId,
                'name' => 'VO2 Max',
            ],
            [
                'dsw_type_group_id' => $anaerobicTypeGroupId,
                'name' => 'Anaerobic',
            ],
            [
                'dsw_type_group_id' => $sprintTypeGroupId,
                'name' => 'Sprint',
            ],
        ];

        foreach ($types as $type) {
            DswType::create($type);
        }
    }
}
