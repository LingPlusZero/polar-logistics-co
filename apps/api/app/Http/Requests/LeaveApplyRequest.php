<?php

namespace App\Http\Requests;

use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// 請假申請：只送假別與起日，迄日依假別天數算出
class LeaveApplyRequest extends FormRequest
{
    // 旺季不能請假
    public const PEAK_MONTH = 12;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leave.apply') ?? false;
    }

    public function rules(): array
    {
        return [
            'leaveType' => ['required', Rule::enum(LeaveType::class)],
            'startDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return ['startDate.after_or_equal' => '請假起日不可早於今天'];
    }

    // 請假期間（起迄皆含）；驗證通過後才會呼叫
    public function period(): array
    {
        $start = CarbonImmutable::parse($this->validated('startDate'));
        $days = LeaveType::from($this->validated('leaveType'))->days();

        return [$start, $start->addDays($days - 1)];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                [$start, $end] = $this->period();

                // 請假期間只要有一天落在 12 月就不行（例如 11/28 起請 7 天）
                for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                    if ($day->month === self::PEAK_MONTH) {
                        $validator->errors()->add('startDate', '12 月是旺季，不能請假，大家一起撐下去！');

                        return;
                    }
                }

                // 每個假不能重疊；被駁回的假單不佔用日期
                $overlapping = LeaveRequest::where('elf_id', $this->user()->id)
                    ->notRejected()
                    ->overlapping($start->toDateString(), $end->toDateString())
                    ->exists();

                if ($overlapping) {
                    $validator->errors()->add('startDate', '這段期間已經有請假單，不能重疊');
                }
            },
        ];
    }
}
