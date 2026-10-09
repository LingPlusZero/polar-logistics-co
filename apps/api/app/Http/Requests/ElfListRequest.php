<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// 名冊列表的查詢參數：搜尋、部門篩選、排序、分頁
class ElfListRequest extends FormRequest
{
    public const SORTS = ['number', 'department', 'seniority'];

    public const DEFAULT_PER_PAGE = 10;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('elf.roster') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:50'],
            'departmentId' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
