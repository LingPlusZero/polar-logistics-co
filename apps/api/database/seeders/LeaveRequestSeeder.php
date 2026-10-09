<?php

namespace Database\Seeders;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Elf;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

// 預設假單讓 Demo 有內容：有已核准、已駁回與審核中的假單，也有一位正在假期內的部長（名冊會顯示「請假」），以及一位一直請假、一直被駁回的 E011。
// 日期以「距第一次執行當天的天數」設定（負數為未來）；之後假單可能已被審核，所以不會重新產生
class LeaveRequestSeeder extends Seeder
{
    // [申請人, 假別, 起日（距今幾天前）, 申請日（距今幾天前）, 審核人（null＝審核中）, 審核日（距今幾天前）, 駁回理由（有值＝駁回）]
    private const LEAVES = [
        ['E016', LeaveType::Sick, 20, 21, 'E006', 20, null],
        ['E021', LeaveType::MagicDepletion, 9, 10, 'E006', 9, '人力不足，請改期後重新申請'],
        ['E017', LeaveType::Sick, -2, 1, null, null, null],
        ['E020', LeaveType::MagicDepletion, -3, 0, null, null, null],
        ['E002', LeaveType::HumanSightingTrauma, 5, 6, 'E001', 5, null],
        ['E004', LeaveType::MagicDepletion, -5, 0, null, null, null],
        // E011 長青一直請假，一直被駁回（最後一張還在審核中）
        ['E011', LeaveType::Sick, 60, 61, 'E003', 60, '外勤機動部當週出勤人力不足，請另擇日期'],
        ['E011', LeaveType::MagicDepletion, 45, 46, 'E003', 44, '同一週內已核准其他精靈請假，請改期'],
        ['E011', LeaveType::Sick, 30, 31, 'E003', 30, '煙囪巡檢排程已滿，無法調度，請另擇日期'],
        ['E011', LeaveType::HumanSightingTrauma, 16, 17, 'E003', 15, '事由與假別不符，請與部長確認後重新申請'],
        ['E011', LeaveType::Sick, 4, 5, 'E003', 4, '本月已申請多次，請先與部長確認需求'],
        ['E011', LeaveType::MagicDepletion, -6, 0, null, null, null],
    ];

    public function run(): void
    {
        // 表內已有資料就不灌入，避免還原使用者已審核或新增的假單；須排在 ElfSeeder 之後
        if (LeaveRequest::exists()) {
            return;
        }

        $ids = Elf::pluck('id', 'number');
        $today = CarbonImmutable::today();

        foreach (self::LEAVES as [$applicant, $type, $startAgo, $appliedAgo, $reviewer, $reviewedAgo, $rejectReason]) {
            $start = $today->subDays($startAgo);

            LeaveRequest::create([
                'elf_id' => $ids[$applicant],
                'leave_type' => $type,
                'start_date' => $start,
                'end_date' => $start->addDays($type->days() - 1),
                'applied_at' => $today->subDays($appliedAgo),
                'status' => match (true) {
                    $reviewer === null => LeaveStatus::Pending,
                    $rejectReason !== null => LeaveStatus::Rejected,
                    default => LeaveStatus::Approved,
                },
                'reviewed_at' => $reviewedAgo === null ? null : $today->subDays($reviewedAgo),
                'reviewer_id' => $reviewer === null ? null : $ids[$reviewer],
                'reject_reason' => $rejectReason,
            ]);
        }
    }
}
