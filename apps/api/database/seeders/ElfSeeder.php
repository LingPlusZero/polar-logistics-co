<?php

namespace Database\Seeders;

use App\Enums\ElfRank;
use App\Models\Department;
use App\Models\Elf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// 人員配置來源：docs/admin.md（每個部門至少 1 名正式/資深精靈與 1 名部長、3 名實習生、1 名副聖誕老人）
class ElfSeeder extends Seeder
{
    // 開發測試用的預設密碼，正式環境上線前必須改掉
    private const DEFAULT_PASSWORD = '1qaz@WSX3edc';

    // [編號, 姓名, 部門, 職級, 到職日]
    private const ELVES = [
        ['E001', '白霜', '董事會', ElfRank::ViceSanta, '1987-12-01'],

        ['E002', '雲杉', '禮物包裝部', ElfRank::Minister, '1996-03-12'],
        ['E003', '冬青', '外勤機動部', ElfRank::Minister, '1994-07-02'],
        ['E004', '松果', '馴鹿管理部', ElfRank::Minister, '1998-11-20'],
        ['E005', '寒梅', '乖寶寶稽核部', ElfRank::Minister, '1991-05-09'],
        ['E006', '霧凇', '客戶體驗部', ElfRank::Minister, '1999-01-15'],
        ['E007', '北辰', '運輸部', ElfRank::Minister, '1993-09-28'],
        ['E008', '星砂', '人力資源部', ElfRank::Minister, '1995-02-06'],

        ['E009', '雪松', '禮物包裝部', ElfRank::Senior, '2003-04-18'],
        ['E010', '蕨影', '禮物包裝部', ElfRank::Regular, '2012-08-30'],
        ['E011', '長青', '外勤機動部', ElfRank::Senior, '2005-10-10'],
        ['E012', '岩薔', '外勤機動部', ElfRank::Regular, '2014-06-21'],
        ['E013', '苔蘚', '馴鹿管理部', ElfRank::Regular, '2010-12-05'],
        ['E014', '銀杏', '馴鹿管理部', ElfRank::Senior, '2002-03-03'],
        ['E015', '霜華', '乖寶寶稽核部', ElfRank::Regular, '2011-09-14'],
        ['E016', '夜櫻', '客戶體驗部', ElfRank::Senior, '2006-01-27'],
        ['E017', '晨露', '客戶體驗部', ElfRank::Regular, '2016-07-19'],
        ['E018', '冰晶', '運輸部', ElfRank::Regular, '2013-11-11'],
        ['E019', '月桂', '人力資源部', ElfRank::Senior, '2004-05-23'],

        ['E020', '小雪', '禮物包裝部', ElfRank::Intern, '2026-07-01'],
        ['E021', '細沙', '客戶體驗部', ElfRank::Intern, '2026-07-01'],
        ['E022', '微光', '人力資源部', ElfRank::Intern, '2026-07-01'],
    ];

    public function run(): void
    {
        // 須排在 DepartmentSeeder 之後
        $departments = Department::pluck('id', 'name');

        // 以編號為準，重複執行不會產生重複資料；不覆蓋既有的狀態、備註與已修改的密碼
        $defaultPassword = Hash::make(self::DEFAULT_PASSWORD);

        foreach (self::ELVES as [$number, $name, $department, $rank, $hiredAt]) {
            $elf = Elf::firstOrNew(['number' => $number]);

            // 預設密碼只在新增時設定，重複執行不會蓋掉之後修改的密碼
            if (! $elf->exists) {
                $elf->password = $defaultPassword;
            }

            $elf->fill([
                'name' => $name,
                'department_id' => $departments[$department],
                'rank' => $rank,
                'hired_at' => $hiredAt,
            ])->save();
        }
    }
}
