<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CareerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'departmentId' => $this->department_id,
            // 空值代表不限部門，由前端決定如何顯示
            'department' => $this->department?->name,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'benefits' => $this->benefits,
            'promotion' => $this->promotion,
            'note' => $this->note,
        ];
    }
}
