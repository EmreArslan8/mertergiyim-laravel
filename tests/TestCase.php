<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function afterRefreshingDatabase()
    {
        $this->seed(StorefrontSeeder::class);

        User::factory()->create([
            'email' => 'admin-test@mertergiyim.local',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }
}
