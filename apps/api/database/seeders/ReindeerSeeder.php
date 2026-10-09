<?php

namespace Database\Seeders;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Elf;
use App\Models\LeaveRequest;
use App\Models\Reindeer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

// 預設資料來源：docs/brand.md「車隊動力單元」與 docs/admin.md「動力單位管理」——共 9 隻，編號 01–09；
// 01 魯道夫、07 問題單元、09 新一代前導單元的備註取自 brand.md，07 一直請假一直被駁回。
// 保養日期與假單日期以「距第一次執行當天的天數」設定；之後資料可能已被修改，所以表內已有資料就不再灌入
class ReindeerSeeder extends Seeder
{
    // [編號, 姓名, 到職日, 上次保養（距今幾天前）, 照護專員, 備註]
    private const REINDEER = [
        ['01', '魯道夫', '1939-12-01', 40, 'E014', '前導照明模組搭載單元，想退休但一直被挽留'],
        ['02', '衝刺', '1951-03-14', 75, 'E013', null],
        ['03', '舞步', '1958-03-14', 20, 'E013', null],
        ['04', '疾風', '1966-11-05', 60, 'E014', null],
        ['05', '彗星', '1975-06-18', 88, 'E013', null],
        ['06', '邱比特', '1984-02-09', 33, 'E014', null],
        ['07', '雷霆', '1996-10-22', 95, 'E013', '問題單位不擅與人合作（已經讓 3 人離職）'],
        ['08', '閃電', '2009-07-30', 12, 'E014', null],
        ['09', '極光', '2025-10-01', 5, 'E004', '新一代動力單元，更佳的續航力、更穩定的夜間視野，前導照明模組搭載單元'],
    ];

    // 07 雷霆的假單：[假別, 起日（距今幾天前，負數為未來）, 申請日, 審核日（null＝審核中）, 駁回理由]，由照護專員 E013 代請、E004 審核
    private const PROBLEM_UNIT_LEAVES = [
        [LeaveType::Sick, 50, 51, 50, '馴鹿管理部當週出勤人力不足，請另擇日期'],
        [LeaveType::MagicDepletion, 38, 39, 37, '雪橇編隊需完整配置，請改期'],
        [LeaveType::Sick, 25, 26, 25, '本季已申請多次，請先與部長確認需求'],
        [LeaveType::HumanSightingTrauma, 12, 13, 11, '事由與假別不符，請重新確認後申請'],
        [LeaveType::Sick, -4, 0, null, null],
    ];

    public function run(): void
    {
        // 表內已有資料就不灌入，避免還原使用者已修改或刪除的資料；須排在 ElfSeeder 之後
        if (Reindeer::exists()) {
            return;
        }

        $ids = Elf::pluck('id', 'number');
        $today = CarbonImmutable::today();
        $problemUnit = null;

        foreach (self::REINDEER as [$number, $name, $hiredAt, $maintainedAgo, $caretaker, $note]) {
            $reindeer = Reindeer::create([
                'number' => $number,
                'name' => $name,
                'hired_at' => $hiredAt,
                'last_maintained_at' => $today->subDays($maintainedAgo),
                'caretaker_id' => $ids[$caretaker],
                'note' => $note,
            ]);

            if ($number === '07') {
                $problemUnit = $reindeer;
            }
        }

        foreach (self::PROBLEM_UNIT_LEAVES as [$type, $startAgo, $appliedAgo, $reviewedAgo, $rejectReason]) {
            $start = $today->subDays($startAgo);

            LeaveRequest::create([
                'elf_id' => $problemUnit->caretaker_id,
                'reindeer_id' => $problemUnit->id,
                'leave_type' => $type,
                'start_date' => $start,
                'end_date' => $start->addDays($type->days() - 1),
                'applied_at' => $today->subDays($appliedAgo),
                'status' => $reviewedAgo === null ? LeaveStatus::Pending : LeaveStatus::Rejected,
                'reviewed_at' => $reviewedAgo === null ? null : $today->subDays($reviewedAgo),
                'reviewer_id' => $reviewedAgo === null ? null : $ids['E004'],
                'reject_reason' => $rejectReason,
            ]);
        }
    }
}
