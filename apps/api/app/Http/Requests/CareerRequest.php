<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 精靈管理系統的登入驗證尚未實作，之後在這裡接上權限判斷
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            // 空值代表不限部門
            'departmentId' => ['nullable', 'integer', 'exists:department,id'],
            'description' => ['required', 'string', 'max:5000'],
            'requirements' => ['required', 'string', 'max:5000'],
            'benefits' => ['nullable', 'string', 'max:2000'],
            'promotion' => ['nullable', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    // 對外欄位是 camelCase，轉成資料表欄位；未帶 departmentId 視為不限部門
    public function careerData(): array
    {
        $validated = $this->validated();

        $data = collect($validated)->except('departmentId')->all();
        $data['department_id'] = $validated['departmentId'] ?? null;

        return $data;
    }
}
