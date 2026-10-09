<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reindeer extends Model
{
    // 保養間隔（月），下次保養日期由上次保養日期算出
    public const MAINTENANCE_INTERVAL_MONTHS = 3;

    // 照護專員必須屬於這個部門
    public const CARETAKER_DEPARTMENT = '馴鹿管理部';

    protected $table = 'reindeer';

    protected $fillable = ['number', 'name', 'hired_at', 'last_maintained_at', 'caretaker_id', 'note'];

    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'last_maintained_at' => 'date',
        ];
    }

    // 下一個編號：數字部分最大值 + 1，至少兩位數（01、10、100）；在 PHP 端算，撞號時由 unique 索引擋下
    public static function nextNumber(): string
    {
        $max = static::query()->pluck('number')->map(fn (string $number) => (int) $number)->max() ?? 0;

        return sprintf('%02d', $max + 1);
    }

    // 下次保養日期：上次保養後滿 3 個月（月底不溢位，例如 11/30 + 3 個月 = 2/28）
    public function nextMaintenanceAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->last_maintained_at)->addMonthsNoOverflow(self::MAINTENANCE_INTERVAL_MONTHS);
    }

    public function caretaker(): BelongsTo
    {
        return $this->belongsTo(Elf::class, 'caretaker_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
