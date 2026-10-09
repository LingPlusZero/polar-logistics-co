<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leave.review') ?? false;
    }

    public function rules(): array
    {
        // 駁回一定要有理由
        return ['reason' => ['required', 'string', 'max:500']];
    }
}
