<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AnnualStaticSeeder::class,
            // 職缺依賴部門，須排在 DepartmentSeeder 之後
            DepartmentSeeder::class,
            CareerSeeder::class,
        ]);
    }
}
