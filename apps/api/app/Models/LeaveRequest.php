<?php

namespace App\Models;

use App\Enums\ElfRank;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $table = 'leave_request';

    protected $fillable = [
        'elf_id', 'reindeer_id', 'leave_type', 'start_date', 'end_date', 'applied_at',
        'status', 'reviewed_at', 'reviewer_id', 'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'leave_type' => LeaveType::class,
            'status' => LeaveStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'applied_at' => 'date',
            'reviewed_at' => 'date',
        ];
    }

    public function elf(): BelongsTo
    {
        return $this->belongsTo(Elf::class);
    }

    // 代請假的馴鹿；null＝精靈替自己請假
    public function reindeer(): BelongsTo
    {
        return $this->belongsTo(Reindeer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Elf::class, 'reviewer_id');
    }

    // 與 $start～$end（含）有重疊的假單
    public function scopeOverlapping(Builder $query, string $start, string $end): void
    {
        // 起日用「小於迄日隔天」比對：經 model 寫入的日期在部分資料庫（SQLite）會帶時間，直接比 <= 日期字串會漏掉當天
        $query->where('start_date', '<', CarbonImmutable::parse($end)->addDay()->toDateString())->where('end_date', '>=', $start);
    }

    // 駁回的假單不佔用日期，其餘（審核中、核准）都算
    public function scopeNotRejected(Builder $query): void
    {
        $query->where('status', '!=', LeaveStatus::Rejected->value);
    }

    // 該日正在請假（核准且日期涵蓋）
    public function scopeApprovedOn(Builder $query, string $date): void
    {
        $query->where('status', LeaveStatus::Approved->value)
            ->where('start_date', '<', CarbonImmutable::parse($date)->addDay()->toDateString())
            ->where('end_date', '>=', $date);
    }

    // 審核範圍（docs/admin.md）：部長審自己部門的假單（含實習生，不含自己與其他部長），
    // 副聖誕老人審各部長的假單，也審自己的（沒有上層）；其他職級沒有審核對象
    public function scopeReviewableBy(Builder $query, Elf $reviewer): void
    {
        $query->whereHas('elf', function (Builder $elf) use ($reviewer) {
            match ($reviewer->rank) {
                ElfRank::Minister => $elf->where('department_id', $reviewer->department_id)
                    ->where('rank', '!=', ElfRank::Minister->value),
                ElfRank::ViceSanta => $elf->where(fn (Builder $q) => $q->where('rank', ElfRank::Minister->value)->orWhere('id', $reviewer->id)),
                default => $elf->whereRaw('1 = 0'),
            };
        });
    }
}
