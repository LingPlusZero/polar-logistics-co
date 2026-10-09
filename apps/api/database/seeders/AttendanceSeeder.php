<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Elf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

// 沒有真正的打卡機制，出勤紀錄由這裡產生：每名精靈在「昨天往前 14 天」的平日各一筆。
// 日期依執行當天往前推，所以每次執行（docker 每次啟動都會跑）都重新產生，畫面上永遠有最近的資料。
// 「可能失蹤」的精靈在最後出勤日之後就沒有紀錄（docs/admin.md 的備註呼應）。
// 前端 Demo 的產生規則在 shared/api/demo/attendance.ts，兩邊要保持一致
class AttendanceSeeder extends Seeder
{
    // 產生最近幾天（含昨天，不含今天，因為今天還沒下班）
    private const DAYS = 14;

    // 失蹤精靈最後出勤日距今幾天
    private const LAST_SEEN_DAYS_AGO = [
        'E010' => 7,
        'E012' => 4,
        'E015' => 10,
        'E018' => 8,
    ];

    public function run(): void
    {
        $today = CarbonImmutable::today();
        $firstDay = $today->subDays(self::DAYS);
        $rows = [];

        foreach (Elf::orderBy('number')->get(['id', 'number']) as $elf) {
            $lastSeen = isset(self::LAST_SEEN_DAYS_AGO[$elf->number])
                ? $today->subDays(self::LAST_SEEN_DAYS_AGO[$elf->number])
                : $today->subDay();

            for ($day = $firstDay; $day <= $lastSeen; $day = $day->addDay()) {
                if ($day->isWeekend()) {
                    continue;
                }

                // 用編號與日期的雜湊產生「看起來隨機」的時間，同一天的結果相同
                $startHash = crc32("{$elf->number}|{$day->toDateString()}|in");
                $workHash = crc32("{$elf->number}|{$day->toDateString()}|work");

                // 上班 08:00–09:30（每 5 分鐘）
                $clockIn = $day->setTime(8, 0)->addMinutes(($startHash % 19) * 5);
                // 約 18% 的日子工時不足 8 小時（7:00–7:50），其餘 8:00–10:30
                $workMinutes = $workHash % 100 < 18
                    ? 420 + ($workHash % 11) * 5
                    : 480 + ($workHash % 31) * 5;

                $rows[] = [
                    'elf_id' => $elf->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockIn->addMinutes($workMinutes),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 沒有任何地方會寫入出勤紀錄，整批重建不會弄丟使用者資料
        Attendance::query()->delete();

        foreach (array_chunk($rows, 200) as $chunk) {
            Attendance::insert($chunk);
        }
    }
}
