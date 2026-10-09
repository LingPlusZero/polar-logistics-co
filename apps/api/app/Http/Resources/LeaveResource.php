<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// 請假單；申請人只回傳編號與姓名，審核人只回傳姓名
class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'elfNumber' => $this->elf->number,
            'elfName' => $this->elf->name,
            'leaveType' => $this->leave_type->value,
            'days' => $this->leave_type->days(),
            'startDate' => $this->start_date->toDateString(),
            'endDate' => $this->end_date->toDateString(),
            'appliedAt' => $this->applied_at->toDateString(),
            'status' => $this->status->value,
            'reviewedAt' => $this->reviewed_at?->toDateString(),
            // 審核人姓名；尚未審核或審核人已離職為 null
            'reviewer' => $this->reviewer?->name,
            'rejectReason' => $this->reject_reason,
        ];
    }
}
