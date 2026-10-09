<?php

namespace Tests\Feature;

use App\Enums\ElfRank;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Department;
use App\Models\Elf;
use App\Models\LeaveRequest;
use App\Models\Reindeer;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ElfSeeder;
use Database\Seeders\LeaveRequestSeeder;
use Database\Seeders\ReindeerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReindeerTest extends TestCase
{
    use RefreshDatabase;

    // 馴鹿管理部：部長 T001、照護專員 T002；人力資源部 T003；運輸部 T004
    private Elf $minister;

    private Elf $caretaker;

    private Elf $hr;

    private Elf $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // 固定「今天」，日期相關的規則才不會隨執行時間變動
        $this->travelTo('2026-10-09 10:00:00');

        $this->minister = $this->createElf('馴鹿管理部', 'T001', ElfRank::Minister);
        $this->caretaker = $this->createElf('馴鹿管理部', 'T002', ElfRank::Regular);
        $this->hr = $this->createElf('人力資源部', 'T003', ElfRank::Regular);
        $this->worker = $this->createElf('運輸部', 'T004', ElfRank::Regular);
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

    private function createReindeer(array $overrides = []): Reindeer
    {
        return Reindeer::create([
            'number' => Reindeer::nextNumber(),
            'name' => '測試鹿',
            'hired_at' => '2015-05-05',
            'last_maintained_at' => '2026-08-01',
            'caretaker_id' => $this->caretaker->id,
            ...$overrides,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => '新鹿',
            'hiredAt' => '2026-01-01',
            'lastMaintainedAt' => '2026-09-01',
            'caretakerId' => $this->caretaker->id,
            'note' => null,
            ...$overrides,
        ];
    }

    public function test_只有人力資源部與馴鹿管理部能管理(): void
    {
        $this->getJson('/api/reindeer')->assertUnauthorized();
        $this->withToken('test-token-T004')->getJson('/api/reindeer')->assertForbidden();
        $this->withToken('test-token-T004')->postJson('/api/reindeer', $this->payload())->assertForbidden();
        $this->withToken('test-token-T004')->getJson('/api/reindeer/caretakers')->assertForbidden();

        $this->withToken('test-token-T001')->getJson('/api/reindeer')->assertOk();
        $this->withToken('test-token-T003')->getJson('/api/reindeer')->assertOk();
    }

    public function test_清單欄位_下次保養日期為三個月後(): void
    {
        $this->createReindeer(['last_maintained_at' => '2026-08-01']);

        $this->withToken('test-token-T001')->getJson('/api/reindeer')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.number', '01')
            ->assertJsonPath('0.name', '測試鹿')
            ->assertJsonPath('0.hiredAt', '2015-05-05')
            ->assertJsonPath('0.lastMaintainedAt', '2026-08-01')
            ->assertJsonPath('0.nextMaintenanceAt', '2026-11-01')
            ->assertJsonPath('0.caretaker', '精靈T002');
    }

    public function test_下次保養日期遇到月底不溢位(): void
    {
        // 11/30 + 3 個月 = 2/28，不是 3/2
        $this->createReindeer(['last_maintained_at' => '2025-11-30']);

        $this->withToken('test-token-T001')->getJson('/api/reindeer')
            ->assertJsonPath('0.nextMaintenanceAt', '2026-02-28');
    }

    public function test_排序_編號與年資(): void
    {
        $this->createReindeer(['name' => '資淺', 'hired_at' => '2020-01-01']);
        $this->createReindeer(['name' => '資深', 'hired_at' => '2010-01-01']);
        $this->createReindeer(['name' => '中間', 'hired_at' => '2015-01-01']);

        $names = fn (string $query) => collect($this->withToken('test-token-T001')->getJson('/api/reindeer'.$query)->assertOk()->json())
            ->pluck('name')->all();

        $this->assertSame(['資淺', '資深', '中間'], $names(''));
        $this->assertSame(['中間', '資深', '資淺'], $names('?sort=number&order=desc'));
        // 年資越高＝到職日越早
        $this->assertSame(['資深', '中間', '資淺'], $names('?sort=seniority&order=desc'));
        $this->assertSame(['資淺', '中間', '資深'], $names('?sort=seniority&order=asc'));

        $this->withToken('test-token-T001')->getJson('/api/reindeer?sort=name')->assertUnprocessable();
    }

    public function test_新增時編號自動產生(): void
    {
        $this->withToken('test-token-T001')->postJson('/api/reindeer', $this->payload())
            ->assertCreated()
            ->assertJsonPath('number', '01')
            ->assertJsonPath('nextMaintenanceAt', '2026-12-01');

        // 傳來的編號會被忽略
        $this->withToken('test-token-T001')->postJson('/api/reindeer', $this->payload(['number' => '99']))
            ->assertCreated()
            ->assertJsonPath('number', '02');

        $this->createReindeer(['number' => '09']);
        $this->withToken('test-token-T001')->postJson('/api/reindeer', $this->payload())
            ->assertJsonPath('number', '10');
    }

    public function test_新增驗證(): void
    {
        $this->withToken('test-token-T001')->postJson('/api/reindeer', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'hiredAt', 'lastMaintainedAt', 'caretakerId']);

        // 到職日與上次保養日不可晚於今天
        $this->withToken('test-token-T001')->postJson('/api/reindeer', $this->payload(['hiredAt' => '2026-10-10', 'lastMaintainedAt' => '2026-10-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hiredAt', 'lastMaintainedAt']);

        // 照護專員必須是馴鹿管理部的精靈
        $this->withToken('test-token-T001')->postJson('/api/reindeer', $this->payload(['caretakerId' => $this->worker->id]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.caretakerId.0', '照護專員必須是馴鹿管理部的精靈');
        $this->withToken('test-token-T001')->postJson('/api/reindeer', $this->payload(['caretakerId' => 9999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['caretakerId']);

        $this->assertSame(0, Reindeer::count());
    }

    public function test_編輯不會改到編號與到職日(): void
    {
        $reindeer = $this->createReindeer();

        $this->withToken('test-token-T003')->putJson("/api/reindeer/{$reindeer->id}", $this->payload([
            'name' => '改名',
            'number' => '77',
            'hiredAt' => '2026-01-01',
            'lastMaintainedAt' => '2026-10-01',
            'caretakerId' => $this->minister->id,
            'note' => '備註',
        ]))
            ->assertOk()
            ->assertJsonPath('name', '改名')
            ->assertJsonPath('number', '01')
            ->assertJsonPath('hiredAt', '2015-05-05')
            ->assertJsonPath('nextMaintenanceAt', '2027-01-01')
            ->assertJsonPath('caretaker', '精靈T001')
            ->assertJsonPath('note', '備註');

        $this->withToken('test-token-T003')->putJson('/api/reindeer/9999', $this->payload())->assertNotFound();
    }

    public function test_刪除馴鹿_假單一併刪除(): void
    {
        $reindeer = $this->createReindeer();
        LeaveRequest::create([
            'elf_id' => $this->caretaker->id,
            'reindeer_id' => $reindeer->id,
            'leave_type' => LeaveType::Sick,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'applied_at' => '2026-10-09',
            'status' => LeaveStatus::Pending,
        ]);

        $this->withToken('test-token-T001')->deleteJson("/api/reindeer/{$reindeer->id}")->assertNoContent();

        $this->assertSame(0, Reindeer::count());
        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_照護專員離職後馴鹿保留(): void
    {
        $reindeer = $this->createReindeer();

        $this->caretaker->delete();

        $this->withToken('test-token-T001')->getJson('/api/reindeer')
            ->assertJsonPath('0.caretakerId', null)
            ->assertJsonPath('0.caretaker', null);
        $this->assertNotNull($reindeer->fresh());
    }

    public function test_可選的照護專員只有馴鹿管理部(): void
    {
        $this->withToken('test-token-T003')->getJson('/api/reindeer/caretakers')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.number', 'T001')
            ->assertJsonPath('1.name', '精靈T002');
    }

    public function test_我的馴鹿只有自己照護的(): void
    {
        $this->getJson('/api/reindeer/mine')->assertUnauthorized();

        $this->createReindeer(['name' => '我的']);
        $this->createReindeer(['name' => '別人的', 'caretaker_id' => $this->minister->id]);

        $this->withToken('test-token-T002')->getJson('/api/reindeer/mine')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', '我的');
        $this->withToken('test-token-T004')->getJson('/api/reindeer/mine')->assertOk()->assertJsonCount(0);
    }

    public function test_照護專員代馴鹿請假(): void
    {
        $reindeer = $this->createReindeer();

        $this->withToken('test-token-T002')->postJson('/api/leave', [
            'leaveType' => '魔力枯竭假',
            'startDate' => '2026-10-20',
            'reindeerId' => $reindeer->id,
        ])
            ->assertCreated()
            ->assertJsonPath('elfNumber', 'T002')
            ->assertJsonPath('reindeerNumber', '01')
            ->assertJsonPath('reindeerName', '測試鹿')
            ->assertJsonPath('status', '審核中');

        // 一般請假沒有馴鹿欄位
        $this->withToken('test-token-T002')->postJson('/api/leave', ['leaveType' => '普通病假', 'startDate' => '2026-10-20'])
            ->assertCreated()
            ->assertJsonPath('reindeerName', null);
    }

    public function test_只有照護專員能代請_別人的馴鹿與不存在的馴鹿一樣回覆(): void
    {
        $reindeer = $this->createReindeer();
        $body = ['leaveType' => '普通病假', 'startDate' => '2026-10-20'];

        $this->withToken('test-token-T001')->postJson('/api/leave', [...$body, 'reindeerId' => $reindeer->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.reindeerId.0', '只有照護專員能代馴鹿請假');
        $this->withToken('test-token-T002')->postJson('/api/leave', [...$body, 'reindeerId' => 9999])
            ->assertUnprocessable()
            ->assertJsonPath('errors.reindeerId.0', '只有照護專員能代馴鹿請假');

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_重疊檢查_自己與每隻馴鹿分開計算(): void
    {
        $first = $this->createReindeer();
        $second = $this->createReindeer();
        $body = ['leaveType' => '普通病假', 'startDate' => '2026-10-20'];

        // 照護專員自己請假、替兩隻馴鹿請假，同一天互不影響
        $this->withToken('test-token-T002')->postJson('/api/leave', $body)->assertCreated();
        $this->withToken('test-token-T002')->postJson('/api/leave', [...$body, 'reindeerId' => $first->id])->assertCreated();
        $this->withToken('test-token-T002')->postJson('/api/leave', [...$body, 'reindeerId' => $second->id])->assertCreated();

        // 同一隻馴鹿重疊不行
        $this->withToken('test-token-T002')->postJson('/api/leave', [...$body, 'reindeerId' => $first->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.startDate.0', '這段期間已經有請假單，不能重疊');
        // 自己重疊也不行
        $this->withToken('test-token-T002')->postJson('/api/leave', $body)->assertUnprocessable();
    }

    public function test_馴鹿旺季也不能請假(): void
    {
        $reindeer = $this->createReindeer();

        $this->withToken('test-token-T002')->postJson('/api/leave', [
            'leaveType' => '普通病假',
            'startDate' => '2026-12-05',
            'reindeerId' => $reindeer->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['startDate']);
    }

    public function test_馴鹿的假單由照護專員的上層審核(): void
    {
        $reindeer = $this->createReindeer();
        $this->withToken('test-token-T002')->postJson('/api/leave', [
            'leaveType' => '普通病假',
            'startDate' => '2026-10-20',
            'reindeerId' => $reindeer->id,
        ])->assertCreated();

        $leave = LeaveRequest::firstOrFail();

        // 馴鹿管理部部長審核本部門精靈（照護專員）的假單
        $this->withToken('test-token-T001')->getJson('/api/leave/review')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.reindeerName', '測試鹿');
        $this->withToken('test-token-T001')->postJson("/api/leave/{$leave->id}/approve")
            ->assertOk()
            ->assertJsonPath('reindeerName', '測試鹿');
    }

    public function test_馴鹿的假單不影響照護專員的請假狀態_也不列入精靈請假紀錄(): void
    {
        $reindeer = $this->createReindeer();
        LeaveRequest::create([
            'elf_id' => $this->caretaker->id,
            'reindeer_id' => $reindeer->id,
            'leave_type' => LeaveType::HumanSightingTrauma,
            'start_date' => '2026-10-08',
            'end_date' => '2026-10-14',
            'applied_at' => '2026-10-01',
            'status' => LeaveStatus::Approved,
        ]);

        // 馴鹿在請假中，照護專員本人仍是正常
        $this->assertSame('正常', $this->caretaker->fresh()->displayStatus()->value);
        $this->withToken('test-token-T003')->getJson('/api/elf?search=T002')->assertJsonPath('items.0.status', '正常');
        $this->withToken('test-token-T003')->getJson('/api/leave/records')->assertJsonPath('total', 0);
        // 但照護專員自己的「我的請假單」看得到
        $this->withToken('test-token-T002')->getJson('/api/leave/mine')->assertJsonPath('total', 1);
    }

    public function test_馴鹿的請假紀錄(): void
    {
        $reindeer = $this->createReindeer();
        $other = $this->createReindeer();

        foreach ([[$reindeer, '2026-09-01'], [$reindeer, '2026-10-01'], [$other, '2026-10-05']] as [$target, $appliedAt]) {
            LeaveRequest::create([
                'elf_id' => $this->caretaker->id,
                'reindeer_id' => $target->id,
                'leave_type' => LeaveType::Sick,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-01',
                'applied_at' => $appliedAt,
                'status' => LeaveStatus::Rejected,
                'reject_reason' => '人力不足',
            ]);
        }

        $this->withToken('test-token-T001')->getJson("/api/reindeer/{$reindeer->id}/leave")
            ->assertOk()
            ->assertJsonCount(2)
            // 申請日新到舊
            ->assertJsonPath('0.appliedAt', '2026-10-01')
            ->assertJsonPath('0.rejectReason', '人力不足');

        $this->withToken('test-token-T004')->getJson("/api/reindeer/{$reindeer->id}/leave")->assertForbidden();
    }

    public function test_預設動力單位資料(): void
    {
        $this->seed([DepartmentSeeder::class, ElfSeeder::class, ReindeerSeeder::class]);

        $this->assertSame(9, Reindeer::count());
        $this->assertSame(['01', '02', '03', '04', '05', '06', '07', '08', '09'], Reindeer::orderBy('number')->pluck('number')->all());
        $this->assertSame('魯道夫', Reindeer::where('number', '01')->firstOrFail()->name);

        // 照護專員都是馴鹿管理部的精靈
        $departments = Reindeer::with('caretaker.department')->get()->pluck('caretaker.department.name')->unique()->all();
        $this->assertSame(['馴鹿管理部'], $departments);

        // 07 一直請假一直被駁回：4 張駁回、1 張審核中
        $problem = Reindeer::where('number', '07')->firstOrFail();
        $this->assertStringContainsString('已經讓 3 人離職', $problem->note);
        $this->assertSame(4, $problem->leaveRequests()->where('status', LeaveStatus::Rejected->value)->count());
        $this->assertSame(1, $problem->leaveRequests()->where('status', LeaveStatus::Pending->value)->count());

        // 重複執行不會重複灌入
        $this->seed(ReindeerSeeder::class);
        $this->assertSame(9, Reindeer::count());
        $this->assertSame(5, LeaveRequest::count());
    }

    public function test_預設資料可與精靈假單預設資料並存(): void
    {
        $this->seed([DepartmentSeeder::class, ElfSeeder::class, LeaveRequestSeeder::class, ReindeerSeeder::class]);

        $this->assertSame(17, LeaveRequest::count());
    }
}
