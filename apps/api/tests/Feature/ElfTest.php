<?php

namespace Tests\Feature;

use App\Enums\ElfRank;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Elf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ElfTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Elf $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create(['name' => '禮物包裝部']);

        // 名冊需要 elf.roster 權限（人力資源部），預設以人力資源部的精靈登入
        $this->operator = $this->createElf('人力資源部', 'T000');
        $this->withToken('test-token-T000');
    }

    // 建立指定部門的精靈；有給權杖編號的話可用 test-token-<編號> 登入
    private function createElf(string $departmentName, string $number): Elf
    {
        $department = Department::firstOrCreate(['name' => $departmentName]);

        $elf = Elf::create([
            'number' => $number,
            'name' => '測試精靈',
            'department_id' => $department->id,
            'rank' => ElfRank::Regular,
            'hired_at' => '2020-01-01',
            'password' => 'unused',
        ]);
        $elf->forceFill(['api_token' => hash('sha256', 'test-token-'.$number)])->save();

        return $elf;
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => '新進',
            'departmentId' => $this->department->id,
            'rank' => '實習精靈',
            'hiredAt' => '2026-01-05',
            'status' => '正常',
            'note' => null,
            ...$overrides,
        ];
    }

    public function test_未登入不能使用名冊(): void
    {
        $this->flushHeaders();

        $this->getJson('/api/elf')->assertUnauthorized();
        $this->postJson('/api/elf', $this->payload())->assertUnauthorized();
        $this->putJson("/api/elf/{$this->operator->id}", $this->payload())->assertUnauthorized();
        $this->deleteJson("/api/elf/{$this->operator->id}")->assertUnauthorized();
    }

    public function test_非人力資源部不能使用名冊(): void
    {
        $this->createElf('運輸部', 'T001');
        $this->withToken('test-token-T001');

        $this->getJson('/api/elf')->assertForbidden();
        $this->postJson('/api/elf', $this->payload())->assertForbidden();
        $this->putJson("/api/elf/{$this->operator->id}", $this->payload())->assertForbidden();
        $this->deleteJson("/api/elf/{$this->operator->id}")->assertForbidden();

        $this->assertDatabaseMissing('elves', ['name' => '新進']);
    }

    public function test_取得名冊不含密碼與權杖(): void
    {
        $this->getJson('/api/elf')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.number', 'T000')
            ->assertJsonPath('items.0.department', '人力資源部')
            ->assertJsonPath('items.0.rank', '正式精靈')
            ->assertJsonPath('items.0.hiredAt', '2020-01-01')
            ->assertJsonPath('items.0.status', '正常')
            ->assertJsonMissingPath('items.0.password')
            ->assertJsonMissingPath('items.0.apiToken');
    }

    public function test_名冊帶最後出勤日_沒有紀錄為_null(): void
    {
        $worker = $this->createElf('運輸部', 'T001');
        Attendance::create(['elf_id' => $worker->id, 'clock_in' => '2026-10-06 08:00:00', 'clock_out' => '2026-10-06 17:00:00']);
        Attendance::create(['elf_id' => $worker->id, 'clock_in' => '2026-10-08 08:30:00', 'clock_out' => '2026-10-08 17:30:00']);

        $byNumber = fn () => collect($this->getJson('/api/elf')->json('items'))->keyBy('number');

        $this->assertSame('2026-10-08', $byNumber()['T001']['lastAttendedAt']);
        $this->assertNull($byNumber()['T000']['lastAttendedAt']);

        // 編輯後回傳的資料也帶最後出勤日
        $this->putJson("/api/elf/{$worker->id}", $this->payload(['name' => '改名']))
            ->assertOk()
            ->assertJsonPath('lastAttendedAt', '2026-10-08');
        // 新增的精靈還沒有出勤紀錄
        $this->postJson('/api/elf', $this->payload())->assertCreated()->assertJsonPath('lastAttendedAt', null);
    }

    public function test_分頁(): void
    {
        foreach (range(1, 12) as $i) {
            $this->createElf('運輸部', sprintf('T%03d', $i));
        }

        // 共 13 筆（含登入者），每頁 5 筆
        $this->getJson('/api/elf?perPage=5&page=3')
            ->assertOk()
            ->assertJsonPath('total', 13)
            ->assertJsonPath('page', 3)
            ->assertJsonPath('perPage', 5)
            ->assertJsonPath('lastPage', 3)
            ->assertJsonCount(3, 'items');

        $this->getJson('/api/elf')->assertJsonCount(10, 'items');
        $this->getJson('/api/elf?perPage=51')->assertUnprocessable();
    }

    public function test_搜尋與部門篩選(): void
    {
        $this->createElf('運輸部', 'T001')->update(['name' => '雪花']);
        $this->createElf('運輸部', 'T002');

        $this->getJson('/api/elf?search=雪')->assertJsonPath('total', 1);
        $this->getJson('/api/elf?search=t00')->assertJsonPath('total', 3);
        // % 當一般字元搜尋，不是萬用字元
        $this->getJson('/api/elf?search=%25')->assertJsonPath('total', 0);

        $transport = Department::where('name', '運輸部')->value('id');
        $this->getJson("/api/elf?departmentId={$transport}")->assertJsonPath('total', 2);
        $this->getJson("/api/elf?departmentId={$transport}&search=T001")->assertJsonPath('total', 1);
    }

    public function test_排序(): void
    {
        $this->createElf('運輸部', 'T001')->update(['hired_at' => '2010-01-01']);
        $this->createElf('禮物包裝部', 'T002')->update(['hired_at' => '2024-01-01']);

        $numbers = fn (string $query) => collect($this->getJson("/api/elf?{$query}")->json('items'))->pluck('number')->all();

        $this->assertSame(['T000', 'T001', 'T002'], $numbers('sort=number'));
        $this->assertSame(['T002', 'T001', 'T000'], $numbers('sort=number&order=desc'));
        // 年資高到低＝到職日早到晚
        $this->assertSame(['T001', 'T000', 'T002'], $numbers('sort=seniority&order=desc'));
        $this->assertSame(['T002', 'T000', 'T001'], $numbers('sort=seniority&order=asc'));
        // 部門依 id（建立順序）：禮物包裝部(setUp)、人力資源部、運輸部
        $this->assertSame(['T002', 'T000', 'T001'], $numbers('sort=department'));

        $this->getJson('/api/elf?sort=password')->assertUnprocessable();
    }

    public function test_不能手動設定請假狀態(): void
    {
        $this->postJson('/api/elf', $this->payload(['status' => '請假']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_請假中的精靈修改時狀態維持請假(): void
    {
        $elf = $this->createElf('運輸部', 'T001');
        $elf->update(['status' => '請假']);

        $this->putJson("/api/elf/{$elf->id}", $this->payload(['name' => '改名', 'status' => '正常']))
            ->assertOk()
            ->assertJsonPath('name', '改名')
            ->assertJsonPath('status', '請假');
    }

    public function test_新增精靈使用預設密碼(): void
    {
        $this->postJson('/api/elf', $this->payload())
            ->assertCreated()
            ->assertJsonPath('department', '禮物包裝部');

        $elf = Elf::where('name', '新進')->firstOrFail();
        $this->assertTrue(Hash::check(Elf::defaultPassword(), $elf->password));
    }

    public function test_新增缺少必填欄位會失敗(): void
    {
        $this->postJson('/api/elf', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'departmentId', 'rank', 'status', 'hiredAt']);
    }

    public function test_編號自動產生_接在最大編號後面(): void
    {
        Elf::query()->update(['number' => 'E022']);

        $this->postJson('/api/elf', $this->payload())->assertCreated()->assertJsonPath('number', 'E023');
        // 使用者送來的編號一律忽略
        $this->postJson('/api/elf', $this->payload(['number' => 'E500']))->assertCreated()->assertJsonPath('number', 'E024');
    }

    public function test_編號超過三位數仍會遞增(): void
    {
        Elf::query()->update(['number' => 'E999']);

        $this->postJson('/api/elf', $this->payload())->assertCreated()->assertJsonPath('number', 'E1000');
    }

    public function test_沒有任何E開頭編號時從E001開始(): void
    {
        $this->postJson('/api/elf', $this->payload())->assertCreated()->assertJsonPath('number', 'E001');
    }

    public function test_職級_狀態_部門_到職日不合法會失敗(): void
    {
        $this->postJson('/api/elf', $this->payload([
            'rank' => '大魔王',
            'status' => '在睡覺',
            'departmentId' => 999,
            'hiredAt' => '2999-01-01',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rank', 'status', 'departmentId', 'hiredAt']);
    }

    public function test_編輯不會改到編號與到職日(): void
    {
        $elf = $this->createElf('運輸部', 'T001');

        $this->putJson("/api/elf/{$elf->id}", $this->payload([
            'number' => 'E999',
            'hiredAt' => '2026-01-05',
            'name' => '改名',
            'status' => '可能失蹤',
            'note' => '備註',
        ]))
            ->assertOk()
            ->assertJsonPath('name', '改名')
            ->assertJsonPath('status', '可能失蹤')
            ->assertJsonPath('number', 'T001')
            ->assertJsonPath('hiredAt', '2020-01-01');
    }

    public function test_刪除精靈(): void
    {
        $elf = $this->createElf('運輸部', 'T001');

        $this->deleteJson("/api/elf/{$elf->id}")->assertNoContent();

        $this->assertDatabaseMissing('elves', ['number' => 'T001']);
    }

    public function test_不能刪除自己(): void
    {
        $this->deleteJson("/api/elf/{$this->operator->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', '不能刪除自己');

        $this->assertDatabaseHas('elves', ['number' => 'T000']);
    }

    public function test_編輯不存在的精靈回_404(): void
    {
        $this->putJson('/api/elf/999', $this->payload())->assertNotFound();
    }
}
