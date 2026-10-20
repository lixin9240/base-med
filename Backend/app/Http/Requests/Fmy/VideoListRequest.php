<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dr_id' => ['required', 'string', 'max:32', Rule::exists('visit_record', 'dr_id')],
        ];
    }

    public function messages(): array
    {
        return [
            'dr_id.required' => '匿名编号不能为空',
            'dr_id.string' => '匿名编号格式不正确',
            'dr_id.max' => '匿名编号长度不能超过32个字符',
            'dr_id.exists' => '匿名编号不存在',
        ];
    }
}
