<?php

namespace App\Models;

use App\Enums\ElfRank;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Elf extends Model
{
    protected $table = 'elves';

    protected $fillable = ['number', 'name', 'department_id', 'rank', 'hired_at', 'status', 'note', 'password'];

    // 密碼與權杖雜湊不應出現在任何序列化結果
    protected $hidden = ['password', 'api_token'];

    protected function casts(): array
    {
        return [
            'rank' => ElfRank::class,
            'hired_at' => 'date',
            // 寫入時自動雜湊
            'password' => 'hashed',
        ];
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
        $permissions = ['leave.apply', 'password.change'];

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
