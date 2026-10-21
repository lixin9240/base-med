<?php

namespace App\Services\Wjc\Admin;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\ImportTask;
use App\Models\Question;
use App\Models\Video;
use App\Models\VideoQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 题目配置服务
 *
 * 只做业务逻辑：题目列表、新增、编辑、模板下载、导入、导出、移除。
 */
class VideoQuestionService
{
    /**
     * Excel 模板表头（与前端导入/导出模板一致：视频标题/题目用途/题序/题型/题干/选项A-F/正确答案/答案解析）
     */
    private const TEMPLATE_HEADERS = [
        '视频标题*',
        '题目用途*',
        '题序*',
        '题型*',
        '题干*',
        '选项A*',
        '选项B*',
        '选项C',
        '选项D',
        '选项E',
        '选项F',
        '正确答案*',
        '答案解析',
    ];

    /**
     * 题型英文码 → 中文标签
     */
    private const TYPE_LABELS = [
        'single' => '单选题',
        'multiple' => '多选题',
        'judge' => '判断题',
    ];

    /**
     * 题目统一存储的环节（前后测共用一套，固定按前测存储）
     */
    private const UNIFIED_STAGE = 'pretest';

    /**
     * 获取视频题目列表（前后测共用一套）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function list(array $data): array
    {
        $relations = VideoQuestion::query()
            ->with('question')
            ->where('video_id', $data['video_id'])
            ->orderBy('sort_order')
            ->get();

        $list = $relations->map(function (VideoQuestion $relation) {
            /** @var \App\Models\Question $question */
            $question = $relation->question;

            return [
                'id' => (int) $relation->id,
                'question_id' => (int) $relation->question_id,
                'type' => $question->type,
                'stem' => $question->stem,
                'options' => $question->options,
                'correct_answer' => $question->correct_answer,
                'analysis' => $question->analysis,
                'sort_order' => (int) $relation->sort_order,
            ];
        })->values()->all();

        return [
            'list' => $list,
            'total' => count($list),
        ];
    }

    /**
     * 新增题目：写入 question 题库表并建立 video_question 关联
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、type、stem、options、correct_answer、analysis
     * @return int 题目 ID
     */
    public function add(array $data): int
    {
        $adminId = (int) Auth::guard('admin')->id();

        // 组装题目数据（判断题补默认选项）
        $questionData = $this->buildQuestionData($data, $adminId);

        return (int) \DB::transaction(function () use ($data, $questionData, $adminId) {
            $question = Question::create($questionData);

            // 排序排到本组末位
            $maxSort = (int) VideoQuestion::query()
                ->where('video_id', $data['video_id'])
                ->withTrashed()
                ->max('sort_order');

            VideoQuestion::create([
                'video_id' => $data['video_id'],
                'stage' => self::UNIFIED_STAGE,
                'question_id' => $question->id,
                'sort_order' => $maxSort + 1,
            ]);

            Log::channel('business')->info('新增题目', [
                'admin_id' => $adminId,
                'video_id' => $data['video_id'],
                'question_id' => $question->id,
            ]);

            return $question->id;
        });
    }

    /**
     * 编辑题目（更新 question 题库，不影响 video_question 关联）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、question_id、type、stem、options、correct_answer、analysis
     * @return int 题目 ID
     */
    public function update(array $data): int
    {
        $question = Question::find($data['question_id']);

        if (! $question) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '题目不存在');
        }

        $adminId = (int) Auth::guard('admin')->id();

        \DB::transaction(function () use ($question, $data, $adminId) {
            $question->update($this->buildQuestionData($data, $adminId));

            Log::channel('business')->info('编辑题目', [
                'admin_id' => $adminId,
                'question_id' => $question->id,
                'video_id' => $data['video_id'],
            ]);
        });

        return $question->id;
    }

    /**
     * 下载导入模板（Excel 文件流）
     *
     * @return StreamedResponse
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('题目模板');

        foreach (self::TEMPLATE_HEADERS as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        // 示例行（与官方导入模板一致，前后测共用一套，题目用途按前测示例）
        $examples = [
            ['心肺复苏基础操作', '前测', 1, '单选题', '成人胸外按压的深度至少为？', ['2 厘米', '5 厘米', '7 厘米', '10 厘米'], 'B', '成人胸外按压深度至少 5 厘米、不超过 6 厘米。'],
            ['心肺复苏基础操作', '前测', 2, '判断题', '每次按压后应让胸廓完全回弹。', ['正确', '错误'], 'A', '胸廓完全回弹是保证有效循环的关键。'],
            ['心肺复苏基础操作', '前测', 3, '多选题', '下列哪些情形可以停止心肺复苏？', ['患者恢复自主呼吸与心跳', '专业急救人员接手', '施救者体力耗尽无法继续', '家属要求停止即立即停止'], 'ABC', '复苏终止需依据客观指征或专业判断。'],
        ];

        $row = 2;
        foreach ($examples as $example) {
            [$title, $stageLabel, $order, $typeLabel, $stem, $options, $answer, $analysis] = $example;

            $sheet->setCellValue([1, $row], $title);
            $sheet->setCellValue([2, $row], $stageLabel);
            $sheet->setCellValue([3, $row], $order);
            $sheet->setCellValue([4, $row], $typeLabel);
            $sheet->setCellValue([5, $row], $stem);

            foreach (range('F', 'K') as $column) {
                $sheet->setCellValue($column . $row, $options[ord($column) - ord('F')] ?? '');
            }

            $sheet->setCellValue([12, $row], $answer);
            $sheet->setCellValue([13, $row], $analysis);
            $row++;
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setWidth(25);
        }

        return $this->streamXlsx($spreadsheet, "题目导入模板.xlsx");
    }

    /**
     * 导入本组题目：上传 Excel 并创建导入任务（校验/执行由批量导入模块处理）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、file
     * @return array{task_id: int, task_no: string, status: int}
     */
    public function import(array $data): array
    {
        $adminId = (int) Auth::guard('admin')->id();

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];

        // 存储文件
        $filePath = $file->store(
            'imports/' . date('Ymd'),
            'local'
        );

        if (! $filePath) {
            throw new BusinessException(ResponseCode::FILE_UPLOAD_FAILED);
        }

        // 统计总行数（跳过表头）
        $totalRows = $this->countExcelRows(Storage::disk('local')->path($filePath));

        $taskNo = $this->generateTaskNo();

        $task = ImportTask::create([
            'task_no' => $taskNo,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'total_rows' => $totalRows,
            'valid_rows' => 0,
            'error_rows' => 0,
            'status' => 1, // 1 校验中（异步校验由批量导入模块推进）
            'created_by' => $adminId,
        ]);

        Log::channel('business')->info('创建题目导入任务', [
            'admin_id' => $adminId,
            'task_id' => $task->id,
            'task_no' => $taskNo,
            'video_id' => $data['video_id'],
            'total_rows' => $totalRows,
        ]);

        return [
            'task_id' => (int) $task->id,
            'task_no' => $taskNo,
            'status' => (int) $task->status,
        ];
    }

    /**
     * 导出本组题目（Excel 文件流）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id
     * @return StreamedResponse
     */
    public function export(array $data): StreamedResponse
    {
        $video = Video::find($data['video_id']);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        $relations = VideoQuestion::query()
            ->with('question')
            ->where('video_id', $data['video_id'])
            ->orderBy('sort_order')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('题目模板');

        foreach (self::TEMPLATE_HEADERS as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        $stageLabel = '前测';

        $row = 2;
        foreach ($relations as $relation) {
            /** @var \App\Models\Question $question */
            $question = $relation->question;

            $options = array_map(
                fn (array $option) => $option['content'] ?? '',
                $question->options ?? []
            );

            while (count($options) < 6) {
                $options[] = '';
            }

            $sheet->setCellValue([1, $row], $video->title);
            $sheet->setCellValue([2, $row], $stageLabel);
            $sheet->setCellValue([3, $row], (int) $relation->sort_order);
            $sheet->setCellValue([4, $row], self::TYPE_LABELS[$question->type] ?? $question->type);
            $sheet->setCellValue([5, $row], $question->stem);

            foreach (range('F', 'K') as $column) {
                $sheet->setCellValue($column . $row, $options[ord($column) - ord('F')] ?? '');
            }

            $sheet->setCellValue([12, $row], $question->correct_answer);
            $sheet->setCellValue([13, $row], $question->analysis);
            $row++;
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setWidth(25);
        }

        return $this->streamXlsx($spreadsheet, "题目导出.xlsx");
    }

    /**
     * 移除本组题目（软删除 video_question 关联，不删除 question 题库记录）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、question_id
     */
    public function remove(array $data): void
    {
        $relation = VideoQuestion::query()
            ->where('video_id', $data['video_id'])
            ->where('question_id', $data['question_id'])
            ->first();

        if (! $relation) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '该题目未配置到此视频');
        }

        $adminId = (int) Auth::guard('admin')->id();

        $relation->delete();

        Log::channel('business')->info('移除视频题目关联', [
            'admin_id' => $adminId,
            'video_id' => $data['video_id'],
            'question_id' => $data['question_id'],
        ]);
    }

    /**
     * 组装 question 表数据（含 option_count 计算、判断题默认选项）
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildQuestionData(array $data, int $adminId): array
    {
        $type = $data['type'];

        // 判断题固定「正确/错误」两项
        if ($type === 'judge') {
            $options = [
                ['key' => '正确', 'content' => '正确'],
                ['key' => '错误', 'content' => '错误'],
            ];
            $optionCount = 2;
        } else {
            $options = $data['options'] ?? [];

            if (count($options) < 2) {
                throw new BusinessException(ResponseCode::PARAM_ERROR, '选项至少需要 2 个');
            }

            if (count($options) > 6) {
                throw new BusinessException(ResponseCode::PARAM_ERROR, '选项最多 6 个');
            }

            $optionCount = count($options);
        }

        return [
            'type' => $type,
            'stem' => $data['stem'],
            'options' => $options,
            'option_count' => $optionCount,
            'correct_answer' => $data['correct_answer'],
            'analysis' => $data['analysis'] ?? null,
            'difficulty' => 1,
            'knowledge_point' => '',
            'created_by' => $adminId,
        ];
    }

    /**
     * 统计 Excel 数据行数（跳过表头）
     */
    private function countExcelRows(string $filePath): int
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $rowCount = $spreadsheet->getActiveSheet()->getHighestDataRow();

            return max(0, (int) $rowCount - 1);
        } catch (\Throwable $e) {
            Log::channel('exception')->error('Excel 读取失败', [
                'file' => $filePath,
                'message' => $e->getMessage(),
            ]);

            throw new BusinessException(ResponseCode::FILE_FORMAT_ERROR);
        }
    }

    /**
     * 生成导入任务编号：imp_YYYYMMDD_序号
     */
    private function generateTaskNo(): string
    {
        $date = date('Ymd');
        $prefix = "imp_{$date}_";

        $count = ImportTask::query()
            ->where('task_no', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * 输出 xlsx 文件流并销毁内存中的表格对象
     */
    private function streamXlsx(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($fileName) . '"',
        ]);
    }
}
