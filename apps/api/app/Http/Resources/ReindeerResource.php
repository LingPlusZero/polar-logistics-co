<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// 動力單位的一列；年資由到職日計算，前端自行換算
class ReindeerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'hiredAt' => $this->hired_at->toDateString(),
            'lastMaintainedAt' => $this->last_maintained_at->toDateString(),
            // 自動算出，不可修改
            'nextMaintenanceAt' => $this->nextMaintenanceAt()->toDateString(),
            'caretakerId' => $this->caretaker_id,
            // 照護專員姓名；照護專員離職後為 null
            'caretaker' => $this->caretaker?->name,
            'note' => $this->note,
        ];
    }
}
