<?php

namespace App\Http\Requests;

use App\Enums\ComplaintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintListRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 10;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('elf.complaint') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ComplaintStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
