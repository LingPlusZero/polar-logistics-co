<?php

namespace App\Http\Requests;

use App\Enums\ElfRank;
use App\Enums\ElfStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ElfRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 路由的 RequirePermission 已擋過一次，這裡再確認一次，避免之後路由調整時漏掉
        return $this->user()?->hasPermission('elf.roster') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:50'],
            'departmentId' => ['required', 'integer', 'exists:department,id'],
            'rank' => ['required', Rule::enum(ElfRank::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];

        // 「請假」是有請假申請且正值假期才會有的狀態，不能手動設定，也不能手動改掉
        if (! $this->isOnLeave()) {
            $rules['status'] = ['required', Rule::enum(ElfStatus::class)->only([ElfStatus::Normal, ElfStatus::MaybeMissing])];
        }

        // 精靈編號由系統自動產生，到職日只能在新增時填寫；編輯時不接受（即使送來也忽略）
        if ($this->isMethod('POST')) {
            $rules['hiredAt'] = ['required', 'date_format:Y-m-d', 'before_or_equal:today'];
        }

        return $rules;
    }

    private function isOnLeave(): bool
    {
        return $this->route('elf')?->status === ElfStatus::OnLeave;
    }

    // 對外欄位是 camelCase，轉成資料表欄位
    public function elfData(): array
    {
        $validated = $this->validated();

        $data = collect($validated)->except(['departmentId', 'hiredAt'])->all();
        $data['department_id'] = $validated['departmentId'];

        if (isset($validated['hiredAt'])) {
            $data['hired_at'] = $validated['hiredAt'];
        }

        return $data;
    }
}
