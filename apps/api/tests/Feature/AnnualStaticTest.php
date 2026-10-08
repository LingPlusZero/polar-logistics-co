<?php

namespace Tests\Feature;

use Database\Seeders\AnnualStaticSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualStaticTest extends TestCase
{
    use RefreshDatabase;

    public function test_預設回傳最新十年且由舊到新(): void
    {
        $this->seed(AnnualStaticSeeder::class);

        $years = $this->getJson('/api/statics/annual')
            ->assertOk()
            ->assertJsonCount(10)
            ->json('*.year');

        $this->assertSame(range(2016, 2025), $years);
    }

    public function test_可用_limit_限制筆數(): void
    {
        $this->seed(AnnualStaticSeeder::class);

        $this->getJson('/api/statics/annual?limit=3')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('2.year', 2025);
    }

    public function test_limit_超出範圍會失敗(): void
    {
        $this->getJson('/api/statics/annual?limit=0')->assertUnprocessable();
        $this->getJson('/api/statics/annual?limit=abc')->assertUnprocessable();
    }

    public function test_數據符合產生規則(): void
    {
        $this->seed(AnnualStaticSeeder::class);

        foreach ($this->getJson('/api/statics/annual')->json() as $row) {
            $this->assertGreaterThanOrEqual(3, $row['growthRate']);
            $this->assertLessThanOrEqual(7, $row['growthRate']);
            $this->assertGreaterThanOrEqual(99.91, $row['onTimeRate']);
            $this->assertLessThanOrEqual(99.98, $row['onTimeRate']);
            $this->assertLessThan($row['onTimeRate'], $row['completeRate']);
            $this->assertEquals(100, $row['feedbackRate']);
        }
    }

    public function test_最新一年與首頁數據列一致(): void
    {
        $this->seed(AnnualStaticSeeder::class);

        $this->getJson('/api/statics/annual?limit=1')
            ->assertJsonPath('0.giftsDelivered', 2_230_000_000)
            ->assertJsonPath('0.growthRate', 6.7)
            ->assertJsonPath('0.onTimeRate', 99.97);
    }

    public function test_seeder_重複執行不會產生重複年度(): void
    {
        $this->seed(AnnualStaticSeeder::class);
        $this->seed(AnnualStaticSeeder::class);

        $this->assertDatabaseCount('annual_statics', 10);
    }
}
