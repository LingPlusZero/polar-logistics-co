<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// 精靈名冊的一列；不含密碼與權杖。年資由到職日計算，前端自行換算
class ElfResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'departmentId' => $this->department_id,
            'department' => $this->department->name,
            'rank' => $this->rank->value,
            'hiredAt' => $this->hired_at->toDateString(),
            'status' => $this->displayStatus()->value,
            'note' => $this->note,
            // 最後一次出勤的日期，由出勤紀錄算出（withMax／loadMax 帶入）；沒有紀錄為 null
            'lastAttendedAt' => $this->attendances_max_clock_in
                ? Carbon::parse($this->attendances_max_clock_in)->toDateString()
                : null,
        ];
    }
}
