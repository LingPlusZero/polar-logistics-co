<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// 人力資源部看的申訴紀錄；申訴人只存不回傳（docs/admin.md 的欄位沒有申訴人）
class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'elfNumber' => $this->elf->number,
            'elfName' => $this->elf->name,
            'filedAt' => $this->filed_at->toDateString(),
            'reason' => $this->reason,
            'status' => $this->status->value,
            'resolution' => $this->resolution,
            // 處理人姓名；尚未結案或處理人已離職為 null
            'handler' => $this->handler?->name,
        ];
    }
}
