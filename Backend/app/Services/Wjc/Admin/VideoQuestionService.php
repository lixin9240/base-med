<?php

namespace App\Services\Wjc\Admin;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\ImportTask;
use App\Models\Question;
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
     * Excel 模板表头
     */
    private const TEMPLATE_HEADERS = [
        'type' => '题型(single单选/multiple多选/judge判断)',
        'stem' => '题干',
        'options' => '选项(JSON数组，如[{"key":"A","content":"选项A"}]，判断题可留空)',
        'correct_answer' => '正确答案(单选"A"、多选"A,B,C"、判断"正确/错误")',
        'analysis' => '答案解析',
    ];

    /**
     * 获取前/后测题目列表
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、stage
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function list(array $data): array
    {
        $relations = VideoQuestion::query()
            ->with('question')
            ->where('video_id', $data['video_id'])
            ->where('stage', $data['stage'])
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
     * @param  array<string, mixed>  $data  已校验参数：video_id、stage、type、stem、options、correct_answer、analysis
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
                ->where('stage', $data['stage'])
                ->withTrashed()
                ->max('sort_order');

            VideoQuestion::create([
                'video_id' => $data['video_id'],
                'stage' => $data['stage'],
                'question_id' => $question->id,
                'sort_order' => $maxSort + 1,
            ]);

            Log::channel('business')->info('新增题目', [
                'admin_id' => $adminId,
                'video_id' => $data['video_id'],
                'stage' => $data['stage'],
                'question_id' => $question->id,
            ]);

            return $question->id;
        });
    }

    /**
     * 编辑题目（更新 question 题库，不影响 video_question 关联）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、stage、question_id、type、stem、options、correct_answer、analysis
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
                'stage' => $data['stage'],
            ]);
        });

        return $question->id;
    }

    /**
     * 下载导入模板（Excel 文件流）
     *
     * @param  string  $stage  pretest / posttest（仅用于表头标注）
     * @return StreamedResponse
     */
    public function downloadTemplate(string $stage): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('题目模板');

        $headers = array_values(self::TEMPLATE_HEADERS);
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        // 示例行
        $sheet->setCellValue('A2', 'single');
        $sheet->setCellValue('B2', '成人基础生命支持中，按压与通气的比例应为？');
        $sheet->setCellValue('C2', '[{"key":"A","content":"15:2"},{"key":"B","content":"30:2"}]');
        $sheet->setCellValue('D2', 'B');
        $sheet->setCellValue('E2', '成人基础生命支持推荐按压与通气比例为 30:2。');

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(60);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(50);

        return $this->streamXlsx($spreadsheet, "题目导入模板_{$stage}.xlsx");
    }

    /**
     * 导入本组题目：上传 Excel 并创建导入任务（校验/执行由批量导入模块处理）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、stage、file
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
            'stage' => $data['stage'],
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
     * @param  array<string, mixed>  $data  已校验参数：video_id、stage
     * @return StreamedResponse
     */
    public function export(array $data): StreamedResponse
    {
        $relations = VideoQuestion::query()
            ->with('question')
            ->where('video_id', $data['video_id'])
            ->where('stage', $data['stage'])
            ->orderBy('sort_order')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('题目导出');

        $headers = array_values(self::TEMPLATE_HEADERS);
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        $row = 2;
        foreach ($relations as $relation) {
            /** @var \App\Models\Question $question */
            $question = $relation->question;
            $sheet->setCellValue([1, $row], $question->type);
            $sheet->setCellValue([2, $row], $question->stem);
            $sheet->setCellValue([3, $row], json_encode($question->options, JSON_UNESCAPED_UNICODE));
            $sheet->setCellValue([4, $row], $question->correct_answer);
            $sheet->setCellValue([5, $row], $question->analysis);
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setWidth(40);
        }

        return $this->streamXlsx($spreadsheet, "题目导出_{$data['stage']}.xlsx");
    }

    /**
     * 移除本组题目（软删除 video_question 关联，不删除 question 题库记录）
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、stage、question_id
     */
    public function remove(array $data): void
    {
        $relation = VideoQuestion::query()
            ->where('video_id', $data['video_id'])
            ->where('stage', $data['stage'])
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
            'stage' => $data['stage'],
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
