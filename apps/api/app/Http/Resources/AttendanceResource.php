<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'elfNumber' => $this->elf->number,
            'elfName' => $this->elf->name,
            // 本地時間字串 YYYY-MM-DD HH:mm，不含時區，前端直接顯示
            'clockIn' => $this->clock_in->format('Y-m-d H:i'),
            'clockOut' => $this->clock_out->format('Y-m-d H:i'),
            // 工作時數（分鐘）；前端以 480（8 小時）判斷是否不足
            'workMinutes' => $this->workMinutes(),
        ];
    }
}
