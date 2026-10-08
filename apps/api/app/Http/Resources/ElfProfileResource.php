<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// 登入者自己的資料與權限，不含權杖
class ElfProfileResource extends JsonResource
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
            'permissions' => $this->permissions(),
        ];
    }
}
