<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareLinkVisitRecordListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'link_id' => ['nullable', 'integer'],
            'video_id' => ['nullable', 'integer', Rule::exists('video', 'id')],
            'dr_id' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => '页码必须为整数',
            'page.min' => '页码不能小于1',
            'page_size.integer' => '每页数量必须为整数',
            'page_size.min' => '每页数量不能小于1',
            'page_size.max' => '每页数量不能超过100',
            'link_id.integer' => '链接ID必须为整数',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'dr_id.max' => '匿名编号不能超过32字',
        ];
    }
}