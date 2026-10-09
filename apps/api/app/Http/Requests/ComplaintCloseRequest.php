<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintCloseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('elf.complaint') ?? false;
    }

    public function rules(): array
    {
        // 結案時一定要有後續處理說明
        return ['resolution' => ['required', 'string', 'max:2000']];
    }
}
