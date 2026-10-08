<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'department';

    protected $fillable = ['name', 'duty'];

    public function careers(): HasMany
    {
        return $this->hasMany(Career::class);
    }
}
