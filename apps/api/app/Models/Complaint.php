<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    protected $table = 'complaint';

    protected $fillable = ['elf_id', 'complainant_id', 'reason', 'filed_at', 'status', 'resolution', 'handler_id'];

    protected function casts(): array
    {
        return [
            'filed_at' => 'date',
            'status' => ComplaintStatus::class,
        ];
    }

    // 被申訴人
    public function elf(): BelongsTo
    {
        return $this->belongsTo(Elf::class);
    }

    public function complainant(): BelongsTo
    {
        return $this->belongsTo(Elf::class, 'complainant_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(Elf::class, 'handler_id');
    }
}
