<?php

namespace App\Http\Requests;

use App\Enums\ComplaintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintListRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 10;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('elf.complaint') ?? false;
    }

    public function messages(): array
    {
        return ['dateTo.after_or_equal' => '結束日期不可早於開始日期'];
    }

    public function rules(): array
    {
        return [
            // 被申訴人的精靈編號或姓名模糊搜尋、部門篩選（被申訴人所屬部門）
            'search' => ['nullable', 'string', 'max:50'],
            'departmentId' => ['nullable', 'integer'],
            // 以申訴日期篩選，起迄皆可省略；迄日不可早於起日
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'status' => ['nullable', Rule::enum(ComplaintStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
