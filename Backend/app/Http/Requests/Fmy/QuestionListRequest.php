<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer', Rule::exists('video', 'id')],
            'stage' => ['nullable', 'string', Rule::in(['pretest', 'posttest'])],
        ];
    }

    public function messages(): array
    {
        return [
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'stage.in' => '环节仅支持 pretest 或 posttest',
        ];
    }
}
