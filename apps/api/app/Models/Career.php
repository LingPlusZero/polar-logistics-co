<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Career extends Model
{
    protected $table = 'career';

    protected $fillable = [
        'title',
        'department_id',
        'description',
        'requirements',
        'benefits',
        'note',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
