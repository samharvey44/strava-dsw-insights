<?php

namespace Database\Seeders\Default;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Sam Harvey',
            'email' => config('auth.default_admin_user.email'),
            'password' => Hash::make(config('auth.default_admin_user.password')),
            'admin' => true,
        ]);
    }
}
