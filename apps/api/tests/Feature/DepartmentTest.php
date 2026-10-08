<?php

namespace Tests\Feature;

use App\Models\Department;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_取得部門清單(): void
    {
        $this->seed(DepartmentSeeder::class);

        $this->getJson('/api/department')
            ->assertOk()
            ->assertJsonCount(7)
            ->assertJsonPath('0.name', '禮物包裝部')
            ->assertJsonPath('0.duty', '包禮物');
    }

    public function test_包含運輸部與人力資源部(): void
    {
        $this->seed(DepartmentSeeder::class);

        $names = $this->getJson('/api/department')->json('*.name');

        $this->assertContains('運輸部', $names);
        $this->assertContains('人力資源部', $names);
    }

    public function test_seeder_重複執行不會產生重複部門(): void
    {
        $this->seed(DepartmentSeeder::class);
        $this->seed(DepartmentSeeder::class);

        $this->assertSame(7, Department::count());
    }
}
