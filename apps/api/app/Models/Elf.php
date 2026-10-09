<?php

namespace App\Models;

use App\Enums\ElfRank;
use App\Enums\ElfStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Elf extends Model
{
    // 開發測試用的預設密碼（新增精靈與 ElfSeeder 共用），正式環境上線前必須改掉
    public const DEFAULT_PASSWORD = '1qaz@WSX3edc';

    // 登入後閒置超過這麼久就自動登出（前端 IDLE_TIMEOUT_MS 要同步）
    public const IDLE_TIMEOUT_MINUTES = 30;

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
            'api_token_used_at' => 'datetime',
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // 列表一次查出「今天是否在假期內」（on_leave），避免每列多一次查詢
    public function scopeWithOnLeave(Builder $query): void
    {
        $query->withExists(['leaveRequests as on_leave' => fn ($q) => $q->whereNull('reindeer_id')->approvedOn(now()->toDateString())]);
    }

    // 顯示用狀態：「請假」由請假單決定（核准且今天在假期內），「可能失蹤」優先；
    // 沒有用 scopeWithOnLeave 查出時，這裡再查一次
    public function displayStatus(): ElfStatus
    {
        if ($this->status === ElfStatus::MaybeMissing) {
            return $this->status;
        }

        $onLeave = $this->on_leave ?? $this->leaveRequests()->whereNull('reindeer_id')->approvedOn(now()->toDateString())->exists();

        return $onLeave ? ElfStatus::OnLeave : $this->status;
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
