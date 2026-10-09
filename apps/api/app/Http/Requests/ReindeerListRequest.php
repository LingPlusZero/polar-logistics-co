<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReindeerListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reindeer.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'sort' => ['nullable', 'in:number,seniority'],
            'order' => ['nullable', 'in:asc,desc'],
        ];
    }
}
