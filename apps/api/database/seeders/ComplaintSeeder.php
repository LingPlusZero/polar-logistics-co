<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Elf;
use Illuminate\Database\Seeder;

// 預設資料來源：docs/admin.md「精靈被申訴紀錄」——E016 夜櫻與 E017 晨露一直用小事投訴對方
class ComplaintSeeder extends Seeder
{
    // [被申訴人, 申訴人, 申訴日期, 申訴事由, 後續處理（有值＝已結案）, 處理人]
    private const COMPLAINTS = [
        ['E016', 'E017', '2026-07-14', '午休時間哼歌，而且每天都是同一首', '已請雙方協調午休曲目，申訴人同意改聽雪橇鈴聲白噪音', 'E019'],
        ['E017', 'E016', '2026-07-15', '借走訂書機之後沒有歸還（已借 3 天）', '訂書機已尋回，位於被申訴人抽屜最底層，已歸還', 'E019'],
        ['E016', 'E017', '2026-08-03', '回覆客訴信時使用的驚嘆號比我多', '驚嘆號數量不屬於違規事項，已口頭提醒雙方每封信以兩個為限', 'E008'],
        ['E017', 'E016', '2026-08-21', '把我的雪人造型便利貼貼歪了', '經現場丈量，便利貼歪斜約 2 度，在合理範圍內，不予處分', 'E019'],
        ['E016', 'E017', '2026-09-09', '喝茶時發出聲響', null, null],
        ['E017', 'E016', '2026-09-10', '對我說「早安」的語氣不夠真誠', null, null],
        ['E016', 'E017', '2026-09-28', '在茶水間暖爐前站太久，影響他人取暖', null, null],
        ['E017', 'E016', '2026-10-08', '在我的馬克杯旁邊放了一個一模一樣的馬克杯', null, null],
    ];

    public function run(): void
    {
        // 表內已有資料就不灌入，避免還原使用者已結案或新增的申訴；須排在 ElfSeeder 之後
        if (Complaint::exists()) {
            return;
        }

        $ids = Elf::pluck('id', 'number');

        foreach (self::COMPLAINTS as [$target, $complainant, $filedAt, $reason, $resolution, $handler]) {
            Complaint::create([
                'elf_id' => $ids[$target],
                'complainant_id' => $ids[$complainant],
                'reason' => $reason,
                'filed_at' => $filedAt,
                'status' => $resolution ? ComplaintStatus::Closed : ComplaintStatus::Processing,
                'resolution' => $resolution,
                'handler_id' => $handler ? $ids[$handler] : null,
            ]);
        }
    }
}
