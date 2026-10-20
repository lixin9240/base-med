<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 新增视频请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoCreateRequest extends FormRequest
{
    /**
     * 是否允许该请求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 参数验证规则
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:60'],
            'category_id' => ['required', 'integer', 'min:1'],
            'speaker' => ['required', 'string', 'max:32'],
            'speaker_title' => ['nullable', 'string', 'max:32'],
            'speaker_org' => ['nullable', 'string', 'max:64'],
            'brief_intro' => ['required', 'string', 'max:120'],
            'source_type' => ['required', Rule::in([1, 2])],
            'video_url' => ['nullable', 'string', 'max:256', 'required_if:source_type,1'],
            'cover_url' => ['nullable', 'string', 'max:256'],
            'text_key_points' => ['nullable', 'string'],
            'status' => ['required', Rule::in([0, 1])],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'title.required' => '视频标题不能为空',
            'title.max' => '视频标题不能超过60字',
            'category_id.required' => '分类ID不能为空',
            'category_id.integer' => '分类ID必须为整数',
            'speaker.required' => '主讲人姓名不能为空',
            'speaker.max' => '主讲人姓名不能超过32字',
            'speaker_title.max' => '职称不能超过32字',
            'speaker_org.max' => '主讲人单位不能超过64字',
            'brief_intro.required' => '一句话简介不能为空',
            'brief_intro.max' => '一句话简介不能超过120字',
            'source_type.required' => '视频来源不能为空',
            'source_type.in' => '视频来源不合法',
            'video_url.required_if' => '视频链接不能为空',
            'video_url.max' => '视频链接不能超过256字',
            'cover_url.max' => '封面图地址不能超过256字',
            'status.required' => '上架状态不能为空',
            'status.in' => '上架状态不合法',
            'sort_order.integer' => '排序号必须为整数',
        ];
    }
}
