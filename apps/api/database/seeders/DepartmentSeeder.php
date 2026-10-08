<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

// 資料來源：docs/brand.md「部門與職級」的部門與工作
class DepartmentSeeder extends Seeder
{
    private const DEPARTMENTS = [
        ['name' => '禮物包裝部', 'duty' => '包禮物'],
        ['name' => '外勤機動部', 'duty' => '進入住戶'],
        ['name' => '馴鹿管理部', 'duty' => '運輸動力調度養護'],
        ['name' => '乖寶寶稽核部', 'duty' => '審核名單'],
        ['name' => '客戶體驗部', 'duty' => '處理「我沒收到禮物」'],
        ['name' => '運輸部', 'duty' => '騎雪橇'],
        ['name' => '人力資源部', 'duty' => '精靈HR'],
        // 非業務部門，副聖誕老人所屬；docs/brand.md 未列，無工作內容
        ['name' => '董事會', 'duty' => null],
    ];

    public function run(): void
    {
        // 以名稱為準，重複執行不會產生重複資料
        foreach (self::DEPARTMENTS as $department) {
            Department::updateOrCreate(['name' => $department['name']], $department);
        }
    }
}
