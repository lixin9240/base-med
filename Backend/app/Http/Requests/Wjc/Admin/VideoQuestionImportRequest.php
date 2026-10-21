<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
     * 导入本组题目请求（form-data 上传 Excel）
     *
     * 仅做参数验证，禁止任何数据库/日志/缓存操作。
     */
    class VideoQuestionImportRequest extends FormRequest
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
                'video_id' => ['required', 'integer', Rule::exists('video', 'id')],
                'file' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls,csv',
                    'max:10240',
                ],
            ];
        }

        /**
         * 自定义错误提示
         */
        public function messages(): array
        {
            return [
                'video_id.required' => '视频ID不能为空',
                'video_id.integer' => '视频ID必须为整数',
                'video_id.exists' => '视频不存在',
                'file.required' => '请选择Excel文件',
                'file.mimes' => '仅支持 xlsx / xls / csv 格式',
                'file.max' => '文件大小不能超过10MB',
            ];
        }
    }
