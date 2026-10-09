<?php

namespace App\Http\Requests;

use App\Models\Elf;
use App\Models\Reindeer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReindeerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 路由的 RequirePermission 已擋過一次，這裡再確認一次，避免之後路由調整時漏掉
        return $this->user()?->hasPermission('reindeer.manage') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:50'],
            'lastMaintainedAt' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'caretakerId' => ['required', 'integer', 'exists:elves,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];

        // 編號由系統自動產生，到職日只能在新增時填寫；編輯時不接受（即使送來也忽略）
        if ($this->isMethod('POST')) {
            $rules['hiredAt'] = ['required', 'date_format:Y-m-d', 'before_or_equal:today'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('caretakerId')) {
                    return;
                }

                // 照護專員必須是馴鹿管理部的精靈
                $caretaker = Elf::with('department')->find($this->input('caretakerId'));

                if ($caretaker?->department->name !== Reindeer::CARETAKER_DEPARTMENT) {
                    $validator->errors()->add('caretakerId', '照護專員必須是'.Reindeer::CARETAKER_DEPARTMENT.'的精靈');
                }
            },
        ];
    }

    // 對外欄位是 camelCase，轉成資料表欄位
    public function reindeerData(): array
    {
        $validated = $this->validated();

        $data = [
            'name' => $validated['name'],
            'last_maintained_at' => $validated['lastMaintainedAt'],
            'caretaker_id' => $validated['caretakerId'],
            'note' => $validated['note'] ?? null,
        ];

        if (isset($validated['hiredAt'])) {
            $data['hired_at'] = $validated['hiredAt'];
        }

        return $data;
    }
}
