<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Testler gerçek sipariş/çeviri akışını çalıştırıyor. Dışarıya kaçan bir
        // istek olursa (Telegram bildirimi, Gemini, kur servisi) test patlasın;
        // sessizce canlı servise gitmesin.
        Http::preventStrayRequests();
    }

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
