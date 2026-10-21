<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoProgressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dr_id' => ['required', 'string', 'max:32', Rule::exists('visit_record', 'dr_id')],
            'video_id' => ['required', 'integer', Rule::exists('video', 'id')],
            'watched_seconds' => ['required', 'numeric', 'min:0', 'max:86400'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
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
            'watched_seconds.required' => '已观看时长不能为空',
            'watched_seconds.numeric' => '已观看时长必须是数字',
            'watched_seconds.min' => '已观看时长不能小于0',
            'watched_seconds.max' => '已观看时长超出合法范围',
            'duration.integer' => '视频时长必须为整数',
            'duration.min' => '视频时长不能小于0',
            'duration.max' => '视频时长超出合法范围',
        ];
    }
}
