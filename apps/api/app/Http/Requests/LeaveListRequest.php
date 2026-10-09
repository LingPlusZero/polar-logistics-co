<?php

namespace App\Http\Requests;

use App\Enums\LeaveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// 我的假單、審核清單與請假紀錄共用的查詢參數；權限由路由的 RequirePermission 判斷
class LeaveListRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 10;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function messages(): array
    {
        return ['dateTo.after_or_equal' => '結束日期不可早於開始日期'];
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(LeaveStatus::class)],
            // 以申請日期篩選，起迄皆可省略；迄日不可早於起日
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
