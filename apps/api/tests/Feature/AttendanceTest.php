<?php

namespace Tests\Feature;

use App\Enums\ElfRank;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Elf;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ElfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Elf $hr;

    private Elf $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hr = $this->createElf('人力資源部', 'T001');
        $this->worker = $this->createElf('運輸部', 'T002');
    }

    // 建立精靈，權杖為 test-token-<編號>
    private function createElf(string $departmentName, string $number): Elf
    {
        $department = Department::firstOrCreate(['name' => $departmentName]);

        $elf = Elf::create([
            'number' => $number,
            'name' => '精靈'.$number,
            'department_id' => $department->id,
            'rank' => ElfRank::Regular,
            'hired_at' => '2020-01-01',
            'password' => 'unused',
        ]);
        $elf->forceFill(['api_token' => hash('sha256', 'test-token-'.$number)])->save();

        return $elf;
    }

    private function record(string $in, string $out, ?Elf $elf = null): Attendance
    {
        return Attendance::create([
            'elf_id' => ($elf ?? $this->worker)->id,
            'clock_in' => $in,
            'clock_out' => $out,
        ]);
    }

    public function test_未登入或非人力資源部不能查看(): void
    {
        $this->getJson('/api/attendance')->assertUnauthorized();
        $this->withToken('test-token-T002')->getJson('/api/attendance')->assertForbidden();
    }

    public function test_列表欄位與工作時數(): void
    {
        $this->record('2026-10-08 08:30:00', '2026-10-08 17:00:00');
        $this->record('2026-10-07 09:00:00', '2026-10-07 16:45:00');

        $this->withToken('test-token-T001')->getJson('/api/attendance')
            ->assertOk()
            ->assertJsonPath('total', 2)
            // 新的在前
            ->assertJsonPath('items.0.clockIn', '2026-10-08 08:30')
            ->assertJsonPath('items.0.clockOut', '2026-10-08 17:00')
            ->assertJsonPath('items.0.workMinutes', 510)
            ->assertJsonPath('items.0.elfNumber', 'T002')
            ->assertJsonPath('items.0.elfName', '精靈T002')
            // 不足 8 小時
            ->assertJsonPath('items.1.workMinutes', 465);
    }

    public function test_分頁(): void
    {
        foreach (range(1, 12) as $day) {
            $this->record(sprintf('2026-09-%02d 08:00:00', $day), sprintf('2026-09-%02d 17:00:00', $day));
        }

        $this->withToken('test-token-T001');
        $this->getJson('/api/attendance')->assertJsonCount(10, 'items')->assertJsonPath('lastPage', 2);
        $this->getJson('/api/attendance?page=2')->assertJsonCount(2, 'items');
        $this->getJson('/api/attendance?perPage=51')->assertUnprocessable();
    }

    public function test_搜尋編號姓名與部門篩選(): void
    {
        $this->worker->update(['name' => '雪花']);
        $this->record('2026-10-08 08:00:00', '2026-10-08 17:00:00');
        $this->record('2026-10-08 08:00:00', '2026-10-08 17:00:00', $this->hr);

        $this->withToken('test-token-T001');
        $this->getJson('/api/attendance?search=雪')->assertJsonPath('total', 1)->assertJsonPath('items.0.elfNumber', 'T002');
        $this->getJson('/api/attendance?search=t00')->assertJsonPath('total', 2);
        $this->getJson('/api/attendance?search=T001')->assertJsonPath('total', 1);
        // % 當一般字元搜尋，不是萬用字元
        $this->getJson('/api/attendance?search=%25')->assertJsonPath('total', 0);

        $transport = Department::where('name', '運輸部')->value('id');
        $this->getJson("/api/attendance?departmentId={$transport}")->assertJsonPath('total', 1);
        $this->getJson("/api/attendance?departmentId={$transport}&search=T001")->assertJsonPath('total', 0);
        // 搜尋與日期可以一起用
        $this->getJson('/api/attendance?search=T&dateFrom=2026-10-09')->assertJsonPath('total', 0);
    }

    public function test_日期篩選_起迄含當天(): void
    {
        $this->record('2026-10-06 08:00:00', '2026-10-06 17:00:00');
        $this->record('2026-10-07 23:30:00', '2026-10-08 07:30:00');
        $this->record('2026-10-08 08:00:00', '2026-10-08 17:00:00');
        $this->record('2026-10-09 08:00:00', '2026-10-09 17:00:00');

        $this->withToken('test-token-T001');
        $this->getJson('/api/attendance?dateFrom=2026-10-07&dateTo=2026-10-08')->assertJsonPath('total', 2);
        $this->getJson('/api/attendance?dateFrom=2026-10-08')->assertJsonPath('total', 2);
        $this->getJson('/api/attendance?dateTo=2026-10-06')->assertJsonPath('total', 1);
        $this->getJson('/api/attendance?dateFrom=2026-10-10')->assertJsonPath('total', 0);
    }

    public function test_日期格式錯誤或迄日早於起日會失敗(): void
    {
        $this->withToken('test-token-T001');

        $this->getJson('/api/attendance?dateFrom=昨天')->assertUnprocessable()->assertJsonValidationErrors(['dateFrom']);
        $this->getJson('/api/attendance?dateFrom=2026-10-08&dateTo=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dateTo']);
    }

    public function test_排序_編號與工作時數(): void
    {
        $other = $this->createElf('運輸部', 'T003');
        // T002 工時 9 小時、T003 工時 7 小時 30 分、T002 另一筆 8 小時
        $this->record('2026-10-08 08:00:00', '2026-10-08 17:00:00');
        $this->record('2026-10-08 08:00:00', '2026-10-08 15:30:00', $other);
        $this->record('2026-10-07 08:00:00', '2026-10-07 16:00:00');

        $get = fn (string $query) => collect($this->withToken('test-token-T001')->getJson("/api/attendance?{$query}")->json('items'));

        $this->assertSame([540, 480, 450], $get('sort=workMinutes&order=desc')->pluck('workMinutes')->all());
        $this->assertSame([450, 480, 540], $get('sort=workMinutes&order=asc')->pluck('workMinutes')->all());
        $this->assertSame(['T003', 'T002', 'T002'], $get('sort=number&order=desc')->pluck('elfNumber')->all());
        // 同編號再依上班時間新到舊
        $this->assertSame([540, 480, 450], $get('sort=number&order=asc')->pluck('workMinutes')->all());
        // 下班時間：15:30、16:00、17:00
        $this->assertSame(['2026-10-08 17:00', '2026-10-08 15:30', '2026-10-07 16:00'], $get('sort=clockOut&order=desc')->pluck('clockOut')->all());
        $this->assertSame('2026-10-07 16:00', $get('sort=clockOut&order=asc')->first()['clockOut']);
        // 預設與 sort=clockIn：上班時間，預設新到舊
        $this->assertSame('2026-10-08 08:00', $get('')->first()['clockIn']);
        $this->assertSame('2026-10-07 08:00', $get('sort=clockIn&order=asc')->first()['clockIn']);

        $this->withToken('test-token-T001')->getJson('/api/attendance?sort=password')->assertUnprocessable();
    }

    public function test_沒有新增修改刪除的端點(): void
    {
        $this->withToken('test-token-T001');

        $this->postJson('/api/attendance', [])->assertStatus(405);
        $this->deleteJson('/api/attendance/1')->assertStatus(404);
    }

    public function test_seeder_依當天往前產生_失蹤精靈沒有後續紀錄(): void
    {
        $this->travelTo('2026-10-09 10:00:00');
        $this->seed([DepartmentSeeder::class, ElfSeeder::class]);
        $this->seed(AttendanceSeeder::class);

        $lastDay = fn (string $number) => Attendance::whereHas('elf', fn ($q) => $q->where('number', $number))
            ->max('clock_in');

        // 最近一天是昨天（今天還沒下班）
        $this->assertStringStartsWith('2026-10-08', $lastDay('E001'));
        // E010 最後出勤日距今 7 天＝10/02（週五）
        $this->assertStringStartsWith('2026-10-02', $lastDay('E010'));
        // 週末沒有紀錄；有些日子工時不足 8 小時，畫面才有得標示
        $this->assertFalse(Attendance::all()->contains(fn (Attendance $a) => $a->clock_in->isWeekend()));
        $this->assertTrue(Attendance::all()->contains(fn (Attendance $a) => $a->workMinutes() < 480));
    }

    public function test_seeder_重複執行不會重複_隔天再執行會往後推(): void
    {
        $this->travelTo('2026-10-09 10:00:00');
        $this->seed([DepartmentSeeder::class, ElfSeeder::class]);

        $this->seed(AttendanceSeeder::class);
        $count = Attendance::count();
        $this->seed(AttendanceSeeder::class);
        $this->assertSame($count, Attendance::count());

        // 同一天的紀錄隔天重新產生也一樣（雜湊只看編號與日期）
        $sample = Attendance::where('clock_in', 'like', '2026-10-08%')->orderBy('elf_id')->first();

        $this->travelTo('2026-10-12 10:00:00');
        $this->seed(AttendanceSeeder::class);

        $this->assertSame(1, Attendance::where('elf_id', $sample->elf_id)->where('clock_in', $sample->clock_in)->count());
        // 週一的昨天是週日，最新一筆是上週五 10/09
        $this->assertStringStartsWith('2026-10-09', Attendance::max('clock_in'));
    }
}
