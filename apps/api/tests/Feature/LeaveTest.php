<?php

namespace Tests\Feature;

use App\Enums\ElfRank;
use App\Enums\ElfStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Department;
use App\Models\Elf;
use App\Models\LeaveRequest;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ElfSeeder;
use Database\Seeders\LeaveRequestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    // 運輸部：部長 T001、正式精靈 T002、實習生 T003；禮物包裝部部長 T004；副聖誕老人 T005
    private Elf $minister;

    private Elf $worker;

    private Elf $intern;

    private Elf $otherMinister;

    private Elf $viceSanta;

    protected function setUp(): void
    {
        parent::setUp();

        // 固定「今天」，日期相關的規則才不會隨執行時間變動
        $this->travelTo('2026-10-09 10:00:00');

        $this->minister = $this->createElf('運輸部', 'T001', ElfRank::Minister);
        $this->worker = $this->createElf('運輸部', 'T002', ElfRank::Regular);
        $this->intern = $this->createElf('運輸部', 'T003', ElfRank::Intern);
        $this->otherMinister = $this->createElf('禮物包裝部', 'T004', ElfRank::Minister);
        $this->viceSanta = $this->createElf('董事會', 'T005', ElfRank::ViceSanta);
    }

    // 建立精靈，權杖為 test-token-<編號>
    private function createElf(string $departmentName, string $number, ElfRank $rank): Elf
    {
        $department = Department::firstOrCreate(['name' => $departmentName]);

        $elf = Elf::create([
            'number' => $number,
            'name' => '精靈'.$number,
            'department_id' => $department->id,
            'rank' => $rank,
            'hired_at' => '2020-01-01',
            'password' => 'unused',
        ]);
        $elf->forceFill(['api_token' => hash('sha256', 'test-token-'.$number)])->save();

        return $elf;
    }

    private function createLeave(Elf $elf, array $overrides = []): LeaveRequest
    {
        return LeaveRequest::create([
            'elf_id' => $elf->id,
            'leave_type' => LeaveType::Sick,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'applied_at' => '2026-10-09',
            'status' => LeaveStatus::Pending,
            ...$overrides,
        ]);
    }

    private function apply(string $token, array $body)
    {
        return $this->withToken($token)->postJson('/api/leave', $body);
    }

    public function test_申請後狀態為審核中_迄日依假別天數算出(): void
    {
        $this->apply('test-token-T002', ['leaveType' => '被人類目擊後心理創傷假', 'startDate' => '2026-10-20'])
            ->assertCreated()
            ->assertJsonPath('leaveType', '被人類目擊後心理創傷假')
            ->assertJsonPath('days', 7)
            ->assertJsonPath('startDate', '2026-10-20')
            ->assertJsonPath('endDate', '2026-10-26')
            ->assertJsonPath('appliedAt', '2026-10-09')
            ->assertJsonPath('status', '審核中')
            ->assertJsonPath('reviewedAt', null)
            ->assertJsonPath('reviewer', null);

        $this->apply('test-token-T002', ['leaveType' => '魔力枯竭假', 'startDate' => '2026-11-02'])
            ->assertJsonPath('endDate', '2026-11-03');
        $this->apply('test-token-T002', ['leaveType' => '普通病假', 'startDate' => '2026-11-10'])
            ->assertJsonPath('endDate', '2026-11-10');
    }

    public function test_副聖誕老人的假單自己審(): void
    {
        $this->apply('test-token-T005', ['leaveType' => '普通病假', 'startDate' => '2026-10-20'])
            ->assertCreated()
            ->assertJsonPath('status', '審核中');

        $leave = LeaveRequest::firstOrFail();
        $this->withToken('test-token-T005')->postJson("/api/leave/{$leave->id}/approve")
            ->assertOk()
            ->assertJsonPath('status', '核准')
            ->assertJsonPath('reviewer', '精靈T005');
    }

    public function test_未登入不能使用(): void
    {
        $this->postJson('/api/leave', [])->assertUnauthorized();
        $this->getJson('/api/leave/mine')->assertUnauthorized();
        $this->getJson('/api/leave/review')->assertUnauthorized();
    }

    public function test_申請驗證(): void
    {
        $this->apply('test-token-T002', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['leaveType', 'startDate']);
        $this->apply('test-token-T002', ['leaveType' => '想睡覺假', 'startDate' => '2026-10-20'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['leaveType']);
        $this->apply('test-token-T002', ['leaveType' => '普通病假', 'startDate' => '2026-10-08'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.startDate.0', '請假起日不可早於今天');

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_旺季十二月不能請假_跨入十二月也不行(): void
    {
        $this->apply('test-token-T002', ['leaveType' => '普通病假', 'startDate' => '2026-12-01'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.startDate.0', '12 月是旺季，不能請假，大家一起撐下去！');
        // 11/28 起 7 天會跨到 12/04
        $this->apply('test-token-T002', ['leaveType' => '被人類目擊後心理創傷假', 'startDate' => '2026-11-28'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['startDate']);
        // 11/30 請 1 天沒有碰到 12 月
        $this->apply('test-token-T002', ['leaveType' => '普通病假', 'startDate' => '2026-11-30'])
            ->assertCreated();
    }

    public function test_假單期間不能重疊_駁回的不算(): void
    {
        $this->createLeave($this->worker, [
            'leave_type' => LeaveType::MagicDepletion,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-21',
        ]);

        // 第二天重疊
        $this->apply('test-token-T002', ['leaveType' => '普通病假', 'startDate' => '2026-10-21'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.startDate.0', '這段期間已經有請假單，不能重疊');
        // 新假單包住舊假單
        $this->apply('test-token-T002', ['leaveType' => '被人類目擊後心理創傷假', 'startDate' => '2026-10-16'])
            ->assertUnprocessable();
        // 緊接在後面不重疊
        $this->apply('test-token-T002', ['leaveType' => '普通病假', 'startDate' => '2026-10-22'])->assertCreated();
        // 別人的假單不影響
        $this->apply('test-token-T003', ['leaveType' => '普通病假', 'startDate' => '2026-10-20'])->assertCreated();

        // 駁回的假單不佔用日期
        LeaveRequest::where('elf_id', $this->worker->id)->update(['status' => LeaveStatus::Rejected->value]);
        $this->apply('test-token-T002', ['leaveType' => '普通病假', 'startDate' => '2026-10-21'])->assertCreated();
    }

    public function test_我的假單只看到自己的(): void
    {
        $this->createLeave($this->worker, ['applied_at' => '2026-10-01']);
        $this->createLeave($this->worker, ['applied_at' => '2026-10-05', 'status' => LeaveStatus::Rejected, 'reject_reason' => '人力不足']);
        $this->createLeave($this->intern);

        $this->withToken('test-token-T002')->getJson('/api/leave/mine')
            ->assertOk()
            ->assertJsonPath('total', 2)
            // 申請日新的在前
            ->assertJsonPath('items.0.status', '駁回')
            ->assertJsonPath('items.0.rejectReason', '人力不足')
            ->assertJsonPath('items.0.elfNumber', 'T002');

        $this->withToken('test-token-T002')->getJson('/api/leave/mine?status=審核中')->assertJsonPath('total', 1);
        $this->withToken('test-token-T002')->getJson('/api/leave/mine?status=亂填')->assertUnprocessable();

        // 申請日期含起迄當天
        $this->withToken('test-token-T002')->getJson('/api/leave/mine?dateFrom=2026-10-02')->assertJsonPath('total', 1);
        $this->withToken('test-token-T002')->getJson('/api/leave/mine?dateTo=2026-10-01')->assertJsonPath('total', 1);
        $this->withToken('test-token-T002')->getJson('/api/leave/mine?dateFrom=2026-10-05&dateTo=2026-10-01')->assertUnprocessable();
    }

    public function test_審核權限與範圍(): void
    {
        // 一般精靈沒有審核權限
        $this->withToken('test-token-T002')->getJson('/api/leave/review')->assertForbidden();

        $this->createLeave($this->worker);
        $this->createLeave($this->intern);
        $this->createLeave($this->minister);
        $this->createLeave($this->otherMinister);
        $this->createLeave($this->viceSanta);

        // 部長：自己部門的精靈與實習生，不含自己與其他部長
        $numbers = fn (string $token) => collect($this->withToken($token)->getJson('/api/leave/review')->assertOk()->json('items'))
            ->pluck('elfNumber')->sort()->values()->all();

        $this->assertSame(['T002', 'T003'], $numbers('test-token-T001'));
        // 副聖誕老人：各部長與自己
        $this->assertSame(['T001', 'T004', 'T005'], $numbers('test-token-T005'));
    }

    public function test_部長核准自己部門的假單(): void
    {
        $leave = $this->createLeave($this->intern);

        $this->withToken('test-token-T001')->postJson("/api/leave/{$leave->id}/approve")
            ->assertOk()
            ->assertJsonPath('status', '核准')
            ->assertJsonPath('reviewedAt', '2026-10-09')
            ->assertJsonPath('reviewer', '精靈T001');

        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
    }

    public function test_駁回要有理由(): void
    {
        $leave = $this->createLeave($this->worker);

        $this->withToken('test-token-T001')->postJson("/api/leave/{$leave->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->withToken('test-token-T001')->postJson("/api/leave/{$leave->id}/reject", ['reason' => '人力不足'])
            ->assertOk()
            ->assertJsonPath('status', '駁回')
            ->assertJsonPath('rejectReason', '人力不足');
    }

    public function test_不能審核範圍外的假單(): void
    {
        $other = $this->createLeave($this->otherMinister);
        $ownDepartment = $this->createLeave($this->worker);
        $self = $this->createLeave($this->minister);

        // 部長不能審核其他部長、自己的假單
        $this->withToken('test-token-T001')->postJson("/api/leave/{$other->id}/approve")->assertForbidden();
        $this->withToken('test-token-T001')->postJson("/api/leave/{$self->id}/approve")->assertForbidden();
        // 副聖誕老人只審部長，不審一般精靈
        $this->withToken('test-token-T005')->postJson("/api/leave/{$ownDepartment->id}/approve")->assertForbidden();
        // 其他部門的部長
        $this->withToken('test-token-T004')->postJson("/api/leave/{$ownDepartment->id}/reject", ['reason' => 'x'])->assertForbidden();
        // 一般精靈
        $this->withToken('test-token-T002')->postJson("/api/leave/{$ownDepartment->id}/approve")->assertForbidden();

        $this->assertSame(0, LeaveRequest::where('status', '!=', LeaveStatus::Pending->value)->count());

        // 副聖誕老人審部長
        $this->withToken('test-token-T005')->postJson("/api/leave/{$self->id}/approve")->assertOk();
    }

    public function test_已審核的假單不能再審核(): void
    {
        $leave = $this->createLeave($this->worker);

        $this->withToken('test-token-T001')->postJson("/api/leave/{$leave->id}/approve")->assertOk();
        $this->withToken('test-token-T001')->postJson("/api/leave/{$leave->id}/reject", ['reason' => '反悔'])->assertStatus(409);

        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
    }

    public function test_精靈狀態由請假單決定(): void
    {
        $hr = $this->createElf('人力資源部', 'T006', ElfRank::Regular);
        $this->createLeave($this->worker, [
            'start_date' => '2026-10-08',
            'end_date' => '2026-10-10',
            'status' => LeaveStatus::Approved,
        ]);
        // 審核中與已過的假期都不算請假
        $this->createLeave($this->intern, ['start_date' => '2026-10-08', 'end_date' => '2026-10-10']);
        $this->createLeave($this->minister, ['start_date' => '2026-10-01', 'end_date' => '2026-10-02', 'status' => LeaveStatus::Approved]);

        $statuses = collect($this->withToken('test-token-T006')->getJson('/api/elf?perPage=50')->assertOk()->json('items'))
            ->pluck('status', 'number');

        $this->assertSame('請假', $statuses['T002']);
        $this->assertSame('正常', $statuses['T003']);
        $this->assertSame('正常', $statuses['T001']);

        // 「可能失蹤」優先於請假
        $this->worker->update(['status' => ElfStatus::MaybeMissing]);
        $this->withToken('test-token-T006')->getJson('/api/elf?search=T002')
            ->assertJsonPath('items.0.status', '可能失蹤');
    }

    public function test_請假紀錄只有人力資源部能看(): void
    {
        $this->createElf('人力資源部', 'T006', ElfRank::Regular);

        $this->getJson('/api/leave/records')->assertUnauthorized();
        // 部長只能審核，不能看全部紀錄
        $this->withToken('test-token-T001')->getJson('/api/leave/records')->assertForbidden();
        $this->withToken('test-token-T006')->getJson('/api/leave/records')->assertOk();
    }

    public function test_請假紀錄的搜尋_部門_日期與狀態篩選(): void
    {
        $hr = $this->createElf('人力資源部', 'T006', ElfRank::Regular);

        $this->createLeave($this->worker, ['applied_at' => '2026-10-01', 'status' => LeaveStatus::Rejected, 'reviewed_at' => '2026-10-02', 'reject_reason' => '人力不足']);
        $this->createLeave($this->intern, ['applied_at' => '2026-10-05', 'status' => LeaveStatus::Approved, 'reviewed_at' => '2026-10-06']);
        $this->createLeave($this->otherMinister, ['applied_at' => '2026-10-09']);
        $this->createLeave($hr, ['applied_at' => '2026-09-20']);

        $numbers = fn (string $query = '') => collect($this->withToken('test-token-T006')->getJson('/api/leave/records'.$query)->assertOk()->json('items'))
            ->pluck('elfNumber')->all();

        // 全部精靈、申請日新到舊
        $this->assertSame(['T004', 'T003', 'T002', 'T006'], $numbers());
        // 編號或姓名模糊搜尋
        $this->assertSame(['T003'], $numbers('?search=T003'));
        $this->assertSame(['T003'], $numbers('?search='.urlencode('精靈T003')));
        // 部門（申請人所屬）
        $this->assertSame(['T003', 'T002'], $numbers('?departmentId='.$this->worker->department_id));
        // 申請日期含起迄當天
        $this->assertSame(['T003', 'T002'], $numbers('?dateFrom=2026-10-01&dateTo=2026-10-05'));
        // 審核結果
        $this->assertSame(['T003'], $numbers('?status=核准'));

        $this->withToken('test-token-T006')->getJson('/api/leave/records?status=駁回')
            ->assertJsonPath('items.0.rejectReason', '人力不足');

        $this->withToken('test-token-T006')->getJson('/api/leave/records?dateFrom=2026-10-05&dateTo=2026-10-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dateTo']);
    }

    public function test_預設假單資料(): void
    {
        $this->seed([DepartmentSeeder::class, ElfSeeder::class, LeaveRequestSeeder::class]);

        $this->assertSame(12, LeaveRequest::count());

        // 重複執行不會重複灌入
        $this->seed(LeaveRequestSeeder::class);
        $this->assertSame(12, LeaveRequest::count());

        // 雲杉（E002）正在核准的假期內，名冊顯示請假
        $this->assertSame('請假', Elf::where('number', 'E002')->firstOrFail()->displayStatus()->value);
        $this->assertSame('正常', Elf::where('number', 'E016')->firstOrFail()->displayStatus()->value);
    }
}
