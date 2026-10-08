<?php

namespace Tests\Feature;

use App\Models\Career;
use Database\Seeders\CareerSeeder;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_灌入八個職缺且順序與文件一致(): void
    {
        $this->seed(CareerSeeder::class);

        $titles = Career::orderBy('id')->pluck('title')->all();

        $this->assertCount(8, $titles);
        $this->assertSame('煙囪突入專員', $titles[0]);
        $this->assertSame('實習精靈（全年度招募）', $titles[7]);
    }

    public function test_已有資料時不會重複灌入(): void
    {
        $this->seed(CareerSeeder::class);
        $this->seed(CareerSeeder::class);

        $this->assertDatabaseCount('career', 8);
    }

    public function test_每個職缺都對應到實際部門_實習精靈不限部門(): void
    {
        $this->seed(CareerSeeder::class);

        $jobs = $this->getJson('/api/career')->json();

        foreach (array_slice($jobs, 0, 7) as $job) {
            $this->assertNotNull($job['departmentId'], $job['title']);
        }

        $this->assertSame('外勤機動部', $jobs[0]['department']);
        $this->assertSame('運輸部', $jobs[1]['department']);
        $this->assertSame('人力資源部', $jobs[6]['department']);
        $this->assertNull($jobs[7]['department']);
    }

    public function test_福利與轉正機會欄位(): void
    {
        $this->seed(CareerSeeder::class);

        $this->getJson('/api/career')
            ->assertJsonPath('0.promotion', null)
            ->assertJsonPath('0.benefits', '專屬高風險職務保險、煙灰清潔津貼');

        $this->assertStringContainsString('87%', Career::find(8)->promotion);
    }
}
