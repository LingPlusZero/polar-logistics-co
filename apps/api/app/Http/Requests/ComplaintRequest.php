<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// 我要申訴：所有人都能申訴，申訴日期取當天、狀態預設處理中
class ComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('complaint.file') ?? false;
    }

    public function rules(): array
    {
        return [
            'elfNumber' => ['required', 'string', 'max:10', 'exists:elves,number'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['elfNumber.exists' => '找不到這個精靈編號'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('elfNumber') === $this->user()->number) {
                    $validator->errors()->add('elfNumber', '不能申訴自己');
                }
            },
        ];
    }
}
