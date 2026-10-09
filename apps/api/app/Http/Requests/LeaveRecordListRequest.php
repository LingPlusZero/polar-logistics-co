<?php

namespace App\Http\Requests;

// 精靈請假紀錄（人力資源部）：在共用參數之外，多了搜尋與部門篩選
class LeaveRecordListRequest extends LeaveListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('elf.leave') ?? false;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            // 申請人的精靈編號或姓名模糊搜尋、部門篩選（申請人所屬部門）
            'search' => ['nullable', 'string', 'max:50'],
            'departmentId' => ['nullable', 'integer'],
        ];
    }
}
