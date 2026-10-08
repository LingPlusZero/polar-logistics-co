<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 路由已要求登入；所有精靈都能改自己的密碼
        return $this->user()?->hasPermission('password.change') ?? false;
    }

    // 密碼規則見 docs/admin.md：至少 12 字元，且各一個大寫、小寫、數字、特殊符號
    // 前端 shared/api/password.ts 有同樣的規則，兩邊需一起改
    public function rules(): array
    {
        return [
            'oldPassword' => ['required', 'string', 'max:72'],
            'newPassword' => [
                'required',
                'string',
                'min:12',
                'max:72',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'different:oldPassword',
            ],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ];
    }

    public function messages(): array
    {
        return [
            'newPassword.min' => '新密碼至少需要 12 個字元',
            'newPassword.regex' => '新密碼需包含大寫、小寫、數字與特殊符號各一個',
            'newPassword.different' => '新密碼不可與舊密碼相同',
            'newPasswordConfirmation.same' => '兩次輸入的新密碼不一致',
        ];
    }
}
