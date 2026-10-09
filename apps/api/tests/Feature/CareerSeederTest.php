<?php

namespace Tests\Feature;

use App\Models\Career;
use Database\Seeders\CareerSeeder;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_福利欄位_轉正機會併入福利(): void
    {
        $this->seed(CareerSeeder::class);

        $this->getJson('/api/career')
            ->assertJsonPath('0.benefits', '專屬高風險職務保險、煙灰清潔津貼')
            // 已經沒有獨立的轉正機會欄位
            ->assertJsonMissingPath('0.promotion');

        $this->assertStringContainsString('87%', Career::find(8)->benefits);
    }

    public function test_遷移把既有轉正機會接在福利後面(): void
    {
        $migration = require database_path('migrations/2026_10_14_000003_merge_promotion_into_benefits_on_career_table.php');

        // 還原到遷移之前的欄位，模擬舊資料
        $migration->down();
        Career::query()->delete();
        DB::table('career')->insert([
            ['title' => '有福利', 'description' => 'a', 'requirements' => 'b', 'benefits' => '津貼', 'promotion' => "轉正一\n轉正二"],
            ['title' => '無福利', 'description' => 'a', 'requirements' => 'b', 'benefits' => null, 'promotion' => '轉正三'],
            ['title' => '無轉正', 'description' => 'a', 'requirements' => 'b', 'benefits' => '保險', 'promotion' => null],
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('career', 'promotion'));
        $this->assertSame("津貼\n轉正一\n轉正二", Career::where('title', '有福利')->value('benefits'));
        $this->assertSame('轉正三', Career::where('title', '無福利')->value('benefits'));
        $this->assertSame('保險', Career::where('title', '無轉正')->value('benefits'));
    }
}
