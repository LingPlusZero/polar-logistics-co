<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\ElfRank;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Elf;
use Database\Seeders\ComplaintSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ElfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTest extends TestCase
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

    private function createComplaint(array $overrides = []): Complaint
    {
        return Complaint::create([
            'elf_id' => $this->worker->id,
            'complainant_id' => $this->hr->id,
            'reason' => '小事',
            'filed_at' => '2026-09-01',
            'status' => ComplaintStatus::Processing,
            ...$overrides,
        ]);
    }

    public function test_任何登入者都能申訴_日期為當天_狀態為處理中(): void
    {
        $this->withToken('test-token-T002')
            ->postJson('/api/complaint', ['elfNumber' => 'T001', 'reason' => '喝茶有聲音'])
            ->assertCreated()
            ->assertJsonPath('elfNumber', 'T001')
            ->assertJsonPath('elfName', '精靈T001');

        $complaint = Complaint::firstOrFail();
        $this->assertSame($this->hr->id, $complaint->elf_id);
        $this->assertSame($this->worker->id, $complaint->complainant_id);
        $this->assertSame(now()->toDateString(), $complaint->filed_at->toDateString());
        $this->assertSame(ComplaintStatus::Processing, $complaint->status);
    }

    public function test_未登入不能申訴或查看(): void
    {
        $this->postJson('/api/complaint', ['elfNumber' => 'T001', 'reason' => 'x'])->assertUnauthorized();
        $this->getJson('/api/complaint')->assertUnauthorized();
    }

    public function test_申訴驗證(): void
    {
        $this->withToken('test-token-T002');

        $this->postJson('/api/complaint', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['elfNumber', 'reason']);
        $this->postJson('/api/complaint', ['elfNumber' => 'E404', 'reason' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['elfNumber']);
        $this->postJson('/api/complaint', ['elfNumber' => 'T002', 'reason' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.elfNumber.0', '不能申訴自己');
        $this->postJson('/api/complaint', ['elfNumber' => 'T001', 'reason' => str_repeat('a', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->assertSame(0, Complaint::count());
    }

    public function test_只有人力資源部能查看紀錄(): void
    {
        $this->createComplaint();

        $this->withToken('test-token-T002')->getJson('/api/complaint')->assertForbidden();
        $this->withToken('test-token-T001')->getJson('/api/complaint')->assertOk();
    }

    public function test_紀錄欄位與排序_新的在前(): void
    {
        $this->createComplaint(['filed_at' => '2026-08-01', 'reason' => '舊的']);
        $this->createComplaint(['filed_at' => '2026-09-01', 'reason' => '新的']);

        $this->withToken('test-token-T001')->getJson('/api/complaint')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.reason', '新的')
            ->assertJsonPath('items.0.elfNumber', 'T002')
            ->assertJsonPath('items.0.elfName', '精靈T002')
            ->assertJsonPath('items.0.filedAt', '2026-09-01')
            ->assertJsonPath('items.0.status', '處理中')
            ->assertJsonPath('items.0.handler', null)
            // 申訴人不對外
            ->assertJsonMissingPath('items.0.complainantId');
    }

    public function test_狀態篩選與分頁(): void
    {
        foreach (range(1, 12) as $i) {
            $this->createComplaint();
        }
        $this->createComplaint(['status' => ComplaintStatus::Closed, 'resolution' => '好了', 'handler_id' => $this->hr->id]);

        $this->withToken('test-token-T001');
        $this->getJson('/api/complaint')->assertJsonCount(10, 'items')->assertJsonPath('total', 13);
        $this->getJson('/api/complaint?status=已結案')->assertJsonPath('total', 1);
        $this->getJson('/api/complaint?status=處理中&perPage=5&page=3')->assertJsonCount(2, 'items');
        $this->getJson('/api/complaint?status=亂填')->assertUnprocessable();
    }

    public function test_搜尋_部門_日期篩選(): void
    {
        $this->worker->update(['name' => '雪花']);
        $this->createComplaint(['filed_at' => '2026-10-01', 'reason' => '舊的']);
        $this->createComplaint(['filed_at' => '2026-10-08', 'reason' => '新的', 'elf_id' => $this->hr->id]);

        $this->withToken('test-token-T001');
        // 搜尋與部門都針對被申訴人
        $this->getJson('/api/complaint?search=雪')->assertJsonPath('total', 1)->assertJsonPath('items.0.reason', '舊的');
        $this->getJson('/api/complaint?search=t00')->assertJsonPath('total', 2);
        $this->getJson('/api/complaint?search=%25')->assertJsonPath('total', 0);

        $transport = Department::where('name', '運輸部')->value('id');
        $this->getJson("/api/complaint?departmentId={$transport}")->assertJsonPath('total', 1);

        // 日期含起迄當天，可與其他條件一起用
        $this->getJson('/api/complaint?dateFrom=2026-10-02')->assertJsonPath('total', 1)->assertJsonPath('items.0.reason', '新的');
        $this->getJson('/api/complaint?dateFrom=2026-10-01&dateTo=2026-10-01')->assertJsonPath('total', 1);
        $this->getJson('/api/complaint?dateFrom=2026-10-01&search=T001')->assertJsonPath('total', 1);

        $this->getJson('/api/complaint?dateFrom=2026-10-08&dateTo=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dateTo']);
    }

    public function test_結案需要後續處理說明_處理人自動帶入(): void
    {
        $complaint = $this->createComplaint();
        $this->withToken('test-token-T001');

        $this->postJson("/api/complaint/{$complaint->id}/close", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resolution']);

        $this->postJson("/api/complaint/{$complaint->id}/close", ['resolution' => '已口頭提醒', 'handler' => '偽造'])
            ->assertOk()
            ->assertJsonPath('status', '已結案')
            ->assertJsonPath('resolution', '已口頭提醒')
            ->assertJsonPath('handler', '精靈T001');
    }

    public function test_已結案不能再結案(): void
    {
        $complaint = $this->createComplaint([
            'status' => ComplaintStatus::Closed,
            'resolution' => '舊說明',
            'handler_id' => $this->hr->id,
        ]);

        $this->withToken('test-token-T001')
            ->postJson("/api/complaint/{$complaint->id}/close", ['resolution' => '改寫'])
            ->assertStatus(409);

        $this->assertSame('舊說明', $complaint->fresh()->resolution);
    }

    public function test_非人力資源部不能結案(): void
    {
        $complaint = $this->createComplaint();

        $this->withToken('test-token-T002')
            ->postJson("/api/complaint/{$complaint->id}/close", ['resolution' => 'x'])
            ->assertForbidden();

        $this->assertSame(ComplaintStatus::Processing, $complaint->fresh()->status);
    }

    public function test_結案不存在的申訴回_404(): void
    {
        $this->withToken('test-token-T001')->postJson('/api/complaint/999/close', ['resolution' => 'x'])->assertNotFound();
    }

    public function test_seeder_重複執行不會重複灌入(): void
    {
        $this->seed([DepartmentSeeder::class, ElfSeeder::class]);

        $this->seed(ComplaintSeeder::class);
        $count = Complaint::count();
        $this->seed(ComplaintSeeder::class);

        $this->assertGreaterThanOrEqual(6, $count);
        $this->assertSame($count, Complaint::count());
        // 兩個人一直用小事投訴對方
        $this->assertSame(['E016', 'E017'], Complaint::with('elf')->get()->pluck('elf.number')->unique()->sort()->values()->all());
    }
}
