<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 数据统计医护筛选：is_medical_staff=1 有医护 / 0 无医护（含未采集）
 */
class StatisticsFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_medical_staff' => ['nullable', Rule::in([0, 1, '0', '1'])],
        ];
    }

    public function messages(): array
    {
        return [
            'is_medical_staff.in' => '医护筛选值不合法',
        ];
    }

    /**
     * 未传返回 null（不筛选）；传 0/1 返回对应整数。
     */
    public function medicalStaffFilter(): ?int
    {
        if (! $this->exists('is_medical_staff') || $this->input('is_medical_staff') === null || $this->input('is_medical_staff') === '') {
            return null;
        }

        return (int) $this->input('is_medical_staff');
    }
}
