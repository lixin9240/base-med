<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;

class SpeechSynthesizeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => '提示词不能为空',
            'text.string' => '提示词必须为文本',
            'text.max' => '提示词不能超过 200 字',
        ];
    }
}
