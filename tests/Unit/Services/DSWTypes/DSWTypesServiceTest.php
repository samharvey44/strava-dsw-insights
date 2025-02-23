<?php

namespace Tests\Unit\Services\DSWTypes;

use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Services\DswTypes\DswTypesService;
use Cache;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DSWTypesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_types_no_cache_specified(): void
    {
        $dswTypes = DswType::factory()->count(3)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $this->freezeSecond();

        Cache::shouldReceive('remember')
            ->once()
            ->with(
                'all_dsw_types',
                Mockery::on(fn (CarbonInterface $ttlArg) => $ttlArg->diffInMinutes(now()->endOfDay()) === 0.0),
                Mockery::type('Closure')
            )
            ->andReturn($dswTypes);

        $types = app(DswTypesService::class)->getAllTypes();

        $this->assertEquals($dswTypes->toArray(), $types->toArray());
    }

    public function test_get_all_types_from_cache(): void
    {
        $dswTypes = DswType::factory()->count(3)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $this->freezeSecond();

        Cache::shouldReceive('remember')
            ->once()
            ->with(
                'all_dsw_types',
                Mockery::on(fn (CarbonInterface $ttlArg) => $ttlArg->diffInMinutes(now()->endOfDay()) === 0.0),
                Mockery::type('Closure')
            )
            ->andReturn($dswTypes);

        $types = app(DswTypesService::class)->getAllTypes(true);

        $this->assertEquals($dswTypes->toArray(), $types->toArray());
    }

    public function test_get_all_types_not_from_cache(): void
    {
        $dswTypes = DswType::factory()->count(3)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        Cache::shouldReceive('remember')->never();

        $types = app(DswTypesService::class)->getAllTypes(false);

        $this->assertEquals(
            DswType::whereIn('id', $dswTypes->pluck('id'))->with('typeGroup')->get()->toArray(),
            $types->toArray()
        );
    }
}
