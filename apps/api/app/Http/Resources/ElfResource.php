<?php

namespace App\Http\Resources;

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
            'status' => $this->status->value,
            'note' => $this->note,
        ];
    }
}
