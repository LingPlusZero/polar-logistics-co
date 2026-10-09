<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = ['elf_id', 'clock_in', 'clock_out'];

    protected function casts(): array
    {
        return [
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
        ];
    }

    public function elf(): BelongsTo
    {
        return $this->belongsTo(Elf::class);
    }

    // 工作時數（分鐘），由上下班時間計算
    public function workMinutes(): int
    {
        return (int) $this->clock_in->diffInMinutes($this->clock_out, true);
    }
}
