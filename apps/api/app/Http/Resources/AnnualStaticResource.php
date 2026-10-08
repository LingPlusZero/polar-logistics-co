<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnualStaticResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'year' => $this->year,
            'giftsDelivered' => $this->gifts_delivered,
            'growthRate' => $this->growth_rate,
            'onTimeRate' => $this->on_time_rate,
            'completeRate' => $this->complete_rate,
            'feedbackRate' => $this->feedback_rate,
            'note' => $this->note,
        ];
    }
}
