<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// 出勤紀錄列表的查詢參數：搜尋、部門與日期篩選、排序、分頁
class AttendanceListRequest extends FormRequest
{
    public const SORTS = ['clockIn', 'clockOut', 'number', 'workMinutes'];

    public const DEFAULT_PER_PAGE = 10;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('elf.attendance') ?? false;
    }

    public function rules(): array
    {
        return [
            // 精靈編號或姓名模糊搜尋、部門篩選
            'search' => ['nullable', 'string', 'max:50'],
            'departmentId' => ['nullable', 'integer'],
            // 以上班日期篩選，起迄皆可省略；迄日不可早於起日
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return ['dateTo.after_or_equal' => '結束日期不可早於開始日期'];
    }
}
