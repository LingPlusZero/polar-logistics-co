<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create(['name' => '禮物包裝部']);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'title' => '禮物包裝專員',
            'departmentId' => $this->department->id,
            'description' => '包禮物',
            'requirements' => '手巧',
            'note' => null,
            ...$overrides,
        ];
    }

    private function createCareer(): Career
    {
        return Career::create([
            'title' => '禮物包裝專員',
            'department_id' => $this->department->id,
            'description' => '包禮物',
            'requirements' => '手巧',
        ]);
    }

    public function test_取得職缺清單(): void
    {
        $this->createCareer();

        $this->getJson('/api/career')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', '禮物包裝專員')
            ->assertJsonPath('0.department', '禮物包裝部')
            ->assertJsonPath('0.departmentId', $this->department->id);
    }

    public function test_新增職缺(): void
    {
        $this->postJson('/api/career', $this->payload())
            ->assertCreated()
            ->assertJsonPath('department', '禮物包裝部');

        $this->assertDatabaseCount('career', 1);
    }

    public function test_不限部門的職缺_department_為_null(): void
    {
        $this->postJson('/api/career', $this->payload(['departmentId' => null]))
            ->assertCreated()
            ->assertJsonPath('department', null)
            ->assertJsonPath('departmentId', null);
    }

    public function test_新增職缺缺少必填欄位會失敗(): void
    {
        $this->postJson('/api/career', ['title' => '只有名稱'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description', 'requirements']);
    }

    public function test_部門不存在會失敗(): void
    {
        $this->postJson('/api/career', $this->payload(['departmentId' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['departmentId']);
    }

    public function test_新增職缺欄位超過長度會失敗(): void
    {
        $this->postJson('/api/career', $this->payload(['title' => str_repeat('a', 101)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_編輯職缺(): void
    {
        $career = $this->createCareer();

        $this->putJson("/api/career/{$career->id}", $this->payload(['title' => '資深包裝專員']))
            ->assertOk()
            ->assertJsonPath('title', '資深包裝專員');
    }

    public function test_刪除職缺(): void
    {
        $career = $this->createCareer();

        $this->deleteJson("/api/career/{$career->id}")->assertNoContent();

        $this->assertDatabaseCount('career', 0);
    }

    public function test_編輯不存在的職缺回_404(): void
    {
        $this->putJson('/api/career/999', $this->payload())->assertNotFound();
    }
}
