<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualStatic extends Model
{
    protected $fillable = [
        'year',
        'gifts_delivered',
        'growth_rate',
        'on_time_rate',
        'complete_rate',
        'feedback_rate',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'gifts_delivered' => 'integer',
            'growth_rate' => 'float',
            'on_time_rate' => 'float',
            'complete_rate' => 'float',
            'feedback_rate' => 'float',
        ];
    }
}
