<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoDetailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dr_id' => ['required', 'string', 'max:32', Rule::exists('visit_record', 'dr_id')],
            'video_id' => ['required', 'integer', Rule::exists('video', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'dr_id.required' => '匿名编号不能为空',
            'dr_id.exists' => '匿名编号不存在',
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
        ];
    }
}
