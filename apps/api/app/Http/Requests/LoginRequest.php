<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // 登入只確認有填；密碼規則（至少 12 字元，且各一大寫、小寫、數字、特殊符號）是設定新密碼時才檢查，
    // 規則見 docs/admin.md，前端 shared/api/password.ts 有同樣的規則
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:10'],
            'password' => ['required', 'string', 'max:72'],
        ];
    }
}
