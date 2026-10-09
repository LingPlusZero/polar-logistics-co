<?php

namespace App\Models;

use App\Enums\ElfRank;
use App\Enums\ElfStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Elf extends Model
{
    // 開發測試用的預設密碼（新增精靈與 ElfSeeder 共用），正式環境上線前必須改掉
    public const DEFAULT_PASSWORD = '1qaz@WSX3edc';

    protected $table = 'elves';

    protected $fillable = ['number', 'name', 'department_id', 'rank', 'hired_at', 'status', 'note', 'password'];

    // 密碼與權杖雜湊不應出現在任何序列化結果
    protected $hidden = ['password', 'api_token'];

    protected function casts(): array
    {
        return [
            'rank' => ElfRank::class,
            'status' => ElfStatus::class,
            'hired_at' => 'date',
            // 寫入時自動雜湊
            'password' => 'hashed',
        ];
    }

    // 下一個精靈編號：E 開頭編號中最大的數字 + 1，至少三位數（E001、E023、E1000）。
    // 在 PHP 端算而不用 SQL，避免依賴特定資料庫的字串轉數字語法；撞號時由 unique 索引擋下
    public static function nextNumber(): string
    {
        $max = static::query()
            ->where('number', 'like', 'E%')
            ->pluck('number')
            ->map(fn (string $number) => (int) substr($number, 1))
            ->max() ?? 0;

        return sprintf('E%03d', $max + 1);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    // 權限依職級與部門決定（docs/admin.md）；前端選單依此顯示
    public function permissions(): array
    {
        $permissions = ['leave.apply', 'password.change', 'complaint.file'];

        if ($this->rank->canReviewLeave()) {
            $permissions[] = 'leave.review';
        }

        $departmentName = $this->department->name;

        if ($departmentName === '人力資源部') {
            array_push(
                $permissions,
                'elf.roster',
                'elf.leave',
                'elf.complaint',
                'elf.attendance',
                'career.manage',
            );
        }

        // 動力單位管理：人力資源部、馴鹿管理部
        if (in_array($departmentName, ['人力資源部', '馴鹿管理部'], true)) {
            $permissions[] = 'reindeer.manage';
        }

        return $permissions;
    }
}
