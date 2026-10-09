<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AnnualStaticSeeder::class,
            // 職缺、精靈依賴部門，須排在 DepartmentSeeder 之後
            DepartmentSeeder::class,
            CareerSeeder::class,
            ElfSeeder::class,
            // 申訴紀錄依賴精靈
            ComplaintSeeder::class,
            AttendanceSeeder::class,
            // 假單依賴精靈
            LeaveRequestSeeder::class,
            // 動力單位依賴精靈（照護專員），也會建立馴鹿代請的假單
            ReindeerSeeder::class,
        ]);
    }
}
