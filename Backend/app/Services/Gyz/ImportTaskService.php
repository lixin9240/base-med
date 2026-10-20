<?php

namespace App\Services\Gyz;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Category;
use App\Models\ImportTask;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use App\Models\VideoQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * 批量导入任务服务
 *
 * 只做业务逻辑：任务列表（懒触发校验）、任务详情、确认导入。
 * 导入模板按 G4 全量语义：一次 Excel 导入课程 + 视频（含图文）+ 前测 3 题 + 后测 3 题，
 * 共 4 个 sheet：课程 / 视频 / 前测题目 / 后测题目，「所属视频」来自 Excel 内容本身。
 */
class ImportTaskService
{
    /**
     * 任务状态：0 待校验 1 校验中 2 待确认 3 导入中 4 已完成 5 失败
     */
    private const STATUS_PENDING_VALIDATE = 0;
    private const STATUS_VALIDATING = 1;
    private const STATUS_PENDING_CONFIRM = 2;
    private const STATUS_IMPORTING = 3;
    private const STATUS_COMPLETED = 4;
    private const STATUS_FAILED = 5;

    /**
     * 导入模板 sheet 名称
     */
    private const SHEET_COURSE = '课程';
    private const SHEET_VIDEO = '视频';
    private const SHEET_PRETEST = '前测题目';
    private const SHEET_POSTTEST = '后测题目';

    /**
     * 题型
     */
    private const QUESTION_TYPES = ['single', 'multiple', 'judge'];

    public function __construct(
        private readonly OperationLogService $operationLogService
    ) {
    }

    /**
     * 2.8.1 任务列表
     *
     * 懒触发校验：状态为「校验中」的任务在查询时就地完成校验（项目无队列基础设施），
     * 产出 valid_rows / error_report 后置为「待确认」。
     *
     * @param  array<string, mixed>  $params  已校验参数：page、page_size、status
     * @return array{list: array<int, array<string, mixed>>, total: int, page: int, page_size: int}
     */
    public function taskList(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        // 懒触发校验：就地推进「校验中」的任务
        ImportTask::query()
            ->where('status', self::STATUS_VALIDATING)
            ->get()
            ->each(fn (ImportTask $task) => $this->validate($task));

        $paginator = ImportTask::query()
            ->when(isset($params['status']), fn ($query) => $query->where('status', (int) $params['status']))
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        $list = $paginator->getCollection()
            ->map(fn (ImportTask $task) => $this->mapTask($task))
            ->values()
            ->all();

        return [
            'list' => $list,
            'total' => $paginator->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 2.8.2 任务详情（校验结果与错误报告）
     *
     * @param  int  $taskId  任务 ID
     * @return array<string, mixed>
     */
    public function taskDetail(int $taskId): array
    {
        $task = ImportTask::find($taskId);

        if (! $task) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '导入任务不存在');
        }

        // 「校验中」的任务就地完成校验，保证错误报告可查
        if ((int) $task->status === self::STATUS_VALIDATING) {
            $this->validate($task);
            $task->refresh();
        }

        return $this->mapTask($task) + [
            'error_report' => $task->error_report ?? [],
        ];
    }

    /**
     * 2.8.3 确认导入（仅状态=2 待确认可执行；仅有效行入库）
     *
     * 执行后任务进入「导入中」，写入 question 与 video_question（G4：Excel 一次导入
     * 课程 + 视频 + 图文 + 前测 3 + 后测 3），完成转为「已完成」，异常转为「失败」。
     *
     * @param  int  $taskId  任务 ID
     */
    public function confirm(int $taskId): void
    {
        $task = ImportTask::find($taskId);

        if (! $task) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '导入任务不存在');
        }

        // 「校验中」的任务就地完成校验后再判断状态
        if ((int) $task->status === self::STATUS_VALIDATING) {
            $this->validate($task);
            $task->refresh();
        }

        if ((int) $task->status !== self::STATUS_PENDING_CONFIRM) {
            throw new BusinessException(ResponseCode::STATUS_NOT_OPERABLE, '当前状态不可确认导入');
        }

        $task->update(['status' => self::STATUS_IMPORTING]);

        try {
            DB::transaction(function () use ($task) {
                $this->doImport($task);
            });

            $task->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $task->update(['status' => self::STATUS_FAILED]);

            Log::channel('exception')->error('导入执行失败', [
                'task_id' => $task->id,
                'task_no' => $task->task_no,
                'message' => $e->getMessage(),
            ]);

            throw new BusinessException(ResponseCode::COMMON_OPERATION_FAILED, '导入执行失败');
        }

        $this->operationLogService->record('question', 'import', (int) $task->id, [
            'task_no' => $task->task_no,
            'total_rows' => (int) $task->total_rows,
            'valid_rows' => (int) $task->valid_rows,
        ]);

        Log::channel('business')->info('确认导入完成', [
            'admin_id' => (int) Auth::guard('admin')->id(),
            'task_id' => $task->id,
            'task_no' => $task->task_no,
            'valid_rows' => (int) $task->valid_rows,
        ]);
    }

    /**
     * 校验导入文件并推进任务状态（校验中 → 待确认 / 失败）
     *
     * @param  ImportTask  $task  导入任务
     */
    private function validate(ImportTask $task): void
    {
        try {
            $sheets = $this->readSheets(Storage::disk('local')->path($task->file_path));
        } catch (\Throwable $e) {
            Log::channel('exception')->error('导入文件读取失败', [
                'task_id' => $task->id,
                'file_path' => $task->file_path,
                'message' => $e->getMessage(),
            ]);

            $task->update([
                'status' => self::STATUS_FAILED,
                'valid_rows' => 0,
                'error_rows' => (int) $task->total_rows,
                'error_report' => [['row' => 0, 'sheet' => '-', 'message' => '文件读取失败，请检查文件格式']],
            ]);

            return;
        }

        $errors = [];
        $totalRows = 0;
        $validRows = 0;

        // 分类名称白名单（视频 sheet 校验用）
        $categoryNames = Category::query()->pluck('name')->all();

        $courseNames = [];
        $videoTitles = [];

        // ① 课程 sheet：课程名称 / 封面图URL / 课程简介 / 排序号
        foreach ($this->sheetRows($sheets, self::SHEET_COURSE) as [$rowNo, $row]) {
            $totalRows++;

            $error = $this->validateCourseRow($row);

            if ($error !== null) {
                $errors[] = ['row' => $rowNo, 'sheet' => self::SHEET_COURSE, 'message' => $error];
                continue;
            }

            $courseNames[] = $this->cell($row, 0);
            $validRows++;
        }

        // ② 视频 sheet：视频标题 / 课程名称 / 分类名称 / 主讲人 / 职称 / 主讲人单位 /
        //    一句话简介 / 视频链接 / 封面图URL / 文字要点(图文) / 排序号 / 上架状态
        foreach ($this->sheetRows($sheets, self::SHEET_VIDEO) as [$rowNo, $row]) {
            $totalRows++;

            $error = $this->validateVideoRow($row, $categoryNames, $courseNames);

            if ($error !== null) {
                $errors[] = ['row' => $rowNo, 'sheet' => self::SHEET_VIDEO, 'message' => $error];
                continue;
            }

            $videoTitles[] = $this->cell($row, 0);
            $validRows++;
        }

        // ③④ 前测/后测题目 sheet：所属视频标题 / 题型 / 题干 / 选项JSON / 正确答案 / 答案解析
        foreach ([self::SHEET_PRETEST, self::SHEET_POSTTEST] as $sheetName) {
            foreach ($this->sheetRows($sheets, $sheetName) as [$rowNo, $row]) {
                $totalRows++;

                $error = $this->validateQuestionRow($row, $videoTitles);

                if ($error !== null) {
                    $errors[] = ['row' => $rowNo, 'sheet' => $sheetName, 'message' => $error];
                    continue;
                }

                $validRows++;
            }
        }

        $task->update([
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'error_rows' => count($errors),
            'error_report' => $errors,
            'status' => self::STATUS_PENDING_CONFIRM,
        ]);
    }

    /**
     * 执行导入（仅有效行入库）：课程→topic、视频→video、题目→question+video_question
     *
     * @param  ImportTask  $task  导入任务（状态=待确认）
     */
    private function doImport(ImportTask $task): void
    {
        $sheets = $this->readSheets(Storage::disk('local')->path($task->file_path));
        $adminId = (int) ($task->created_by ?: Auth::guard('admin')->id());

        // 错误行集合（sheet:row），导入时跳过
        $errorKeys = collect($task->error_report ?? [])
            ->map(fn (array $error) => $error['sheet'] . ':' . $error['row'])
            ->all();

        // ① 课程 → topic（同名复用，不重复创建）
        $courseIdByName = [];

        foreach ($this->sheetRows($sheets, self::SHEET_COURSE) as [$rowNo, $row]) {
            if (in_array(self::SHEET_COURSE . ':' . $rowNo, $errorKeys, true)) {
                continue;
            }

            $name = $this->cell($row, 0);

            if (isset($courseIdByName[$name])) {
                continue;
            }

            $topic = Topic::withTrashed()->where('name', $name)->first();

            if (! $topic) {
                $topic = Topic::create([
                    'name' => $name,
                    'cover_url' => $this->cell($row, 1),
                    'intro' => $this->cell($row, 2),
                    'sort_order' => $this->cell($row, 3) !== '' ? (int) $this->cell($row, 3) : 0,
                    'status' => 1,
                ]);
            }

            $courseIdByName[$name] = (int) $topic->id;
        }

        // ② 视频 → video（同名复用，不重复创建）
        $videoIdByTitle = [];

        foreach ($this->sheetRows($sheets, self::SHEET_VIDEO) as [$rowNo, $row]) {
            if (in_array(self::SHEET_VIDEO . ':' . $rowNo, $errorKeys, true)) {
                continue;
            }

            $title = $this->cell($row, 0);

            if (isset($videoIdByTitle[$title])) {
                continue;
            }

            $video = Video::withTrashed()->where('title', $title)->first();

            if (! $video) {
                $category = Category::withTrashed()->where('name', $this->cell($row, 2))->first();

                $video = Video::create([
                    'title' => $title,
                    'category_id' => (int) $category->id,
                    'topic_id' => $courseIdByName[$this->cell($row, 1)] ?? null,
                    'speaker' => $this->cell($row, 3) ?: null,
                    'speaker_title' => $this->cell($row, 4) ?: null,
                    'speaker_org' => $this->cell($row, 5) ?: null,
                    'brief_intro' => $this->cell($row, 6),
                    'text_key_points' => $this->cell($row, 9) ?: null,
                    'source_type' => 1,
                    'video_url' => $this->cell($row, 7),
                    'cover_url' => $this->cell($row, 8),
                    'transcode_status' => 2,
                    'sort_order' => $this->cell($row, 10) !== '' ? (int) $this->cell($row, 10) : 5,
                    'status' => $this->cell($row, 11) !== '' ? (int) $this->cell($row, 11) : 1,
                    'created_by' => $adminId,
                ]);
            }

            $videoIdByTitle[$title] = (int) $video->id;
        }

        // ③④ 题目 → question + video_question（按所属视频分组，排到本组末位）
        foreach ([self::SHEET_PRETEST => 'pretest', self::SHEET_POSTTEST => 'posttest'] as $sheetName => $stage) {
            $sortCounters = [];

            foreach ($this->sheetRows($sheets, $sheetName) as [$rowNo, $row]) {
                if (in_array($sheetName . ':' . $rowNo, $errorKeys, true)) {
                    continue;
                }

                $videoId = $videoIdByTitle[$this->cell($row, 0)] ?? null;

                if ($videoId === null) {
                    continue;
                }

                $question = Question::create($this->buildQuestionData($row, $adminId));

                if (! isset($sortCounters[$videoId])) {
                    $sortCounters[$videoId] = (int) VideoQuestion::query()
                        ->where('video_id', $videoId)
                        ->where('stage', $stage)
                        ->withTrashed()
                        ->max('sort_order');
                }

                $sortCounters[$videoId]++;

                VideoQuestion::create([
                    'video_id' => $videoId,
                    'stage' => $stage,
                    'question_id' => (int) $question->id,
                    'sort_order' => $sortCounters[$videoId],
                ]);
            }
        }

        Log::channel('business')->info('执行导入任务', [
            'task_id' => $task->id,
            'task_no' => $task->task_no,
            'valid_rows' => (int) $task->valid_rows,
        ]);
    }

    /**
     * 读取 Excel 所有 sheet：sheet 名 => 行数组（行号从 1 开始，列为 0 起始索引）
     *
     * @return array<string, array<int, array<int, string>>>
     */
    private function readSheets(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $sheets = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheets[$sheet->getTitle()] = $sheet->toArray(null, true, true, true);
        }

        $spreadsheet->disconnectWorksheets();

        return $sheets;
    }

    /**
     * 遍历 sheet 数据行（跳过表头与整行为空的行），yield [行号, 单元格数组]
     *
     * @param  array<string, array<int, array<int, string>>>  $sheets
     * @return \Generator<int, array{0: int, 1: array<int, string>}>
     */
    private function sheetRows(array $sheets, string $sheetName): \Generator
    {
        $rows = $sheets[$sheetName] ?? [];

        foreach ($rows as $rowNo => $cells) {
            if ((int) $rowNo === 1) {
                continue; // 跳过表头
            }

            $cells = array_values($cells);

            $hasContent = false;

            foreach ($cells as $cellValue) {
                if (trim((string) $cellValue) !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if (! $hasContent) {
                continue; // 跳过整行为空的数据
            }

            yield [(int) $rowNo, $cells];
        }
    }

    /**
     * 取指定下标的单元格文本
     *
     * @param  array<int, string>  $row
     */
    private function cell(array $row, int $index): string
    {
        return trim((string) ($row[$index] ?? ''));
    }

    /**
     * 校验课程行，返回错误信息（null 表示通过）
     *
     * @param  array<int, string>  $row
     */
    private function validateCourseRow(array $row): ?string
    {
        $name = $this->cell($row, 0);

        if ($name === '') {
            return '课程名称不能为空';
        }

        if (mb_strlen($name) > 64) {
            return '课程名称超过64字';
        }

        $intro = $this->cell($row, 2);

        if (mb_strlen($intro) > 500) {
            return '课程简介超过500字';
        }

        $sortOrder = $this->cell($row, 3);

        if ($sortOrder !== '' && ! is_numeric($sortOrder)) {
            return '排序号必须为整数';
        }

        return null;
    }

    /**
     * 校验视频行，返回错误信息（null 表示通过）
     *
     * @param  array<int, string>  $row
     * @param  array<int, string>  $categoryNames  分类名称白名单
     * @param  array<int, string>  $courseNames  本文件「课程」sheet 中的有效课程名
     */
    private function validateVideoRow(array $row, array $categoryNames, array $courseNames): ?string
    {
        $title = $this->cell($row, 0);

        if ($title === '') {
            return '视频标题不能为空';
        }

        if (mb_strlen($title) > 60) {
            return '视频标题超过60字';
        }

        $courseName = $this->cell($row, 1);

        if ($courseName === '') {
            return '所属课程不能为空';
        }

        if (! in_array($courseName, $courseNames, true)) {
            return '所属课程不存在于「课程」sheet';
        }

        $categoryName = $this->cell($row, 2);

        if ($categoryName === '') {
            return '分类名称不能为空';
        }

        if (! in_array($categoryName, $categoryNames, true)) {
            return '分类不存在，请先在分类管理中维护';
        }

        $speakerTitle = $this->cell($row, 4);

        if (mb_strlen($speakerTitle) > 32) {
            return '职称超过32字';
        }

        $speakerOrg = $this->cell($row, 5);

        if (mb_strlen($speakerOrg) > 64) {
            return '主讲人单位超过64字';
        }

        $briefIntro = $this->cell($row, 6);

        if ($briefIntro === '') {
            return '一句话简介不能为空';
        }

        if (mb_strlen($briefIntro) > 120) {
            return '一句话简介超过120字';
        }

        $videoUrl = $this->cell($row, 7);

        if ($videoUrl === '') {
            return '视频链接不能为空';
        }

        $status = $this->cell($row, 11);

        if ($status !== '' && ! in_array($status, ['0', '1'], true)) {
            return '上架状态必须为0或1';
        }

        $sortOrder = $this->cell($row, 10);

        if ($sortOrder !== '' && ! is_numeric($sortOrder)) {
            return '排序号必须为整数';
        }

        return null;
    }

    /**
     * 校验题目行，返回错误信息（null 表示通过）
     *
     * @param  array<int, string>  $row
     * @param  array<int, string>  $videoTitles  本文件「视频」sheet 中的有效视频标题
     */
    private function validateQuestionRow(array $row, array $videoTitles): ?string
    {
        $videoTitle = $this->cell($row, 0);

        if ($videoTitle === '') {
            return '所属视频标题不能为空';
        }

        if (! in_array($videoTitle, $videoTitles, true)) {
            return '所属视频不存在于「视频」sheet';
        }

        $type = $this->cell($row, 1);

        if (! in_array($type, self::QUESTION_TYPES, true)) {
            return '题型必须为 single/multiple/judge';
        }

        $stem = $this->cell($row, 2);

        if ($stem === '') {
            return '题干不能为空';
        }

        if (mb_strlen($stem) > 300) {
            return '题干超过300字';
        }

        // 判断题固定「正确/错误」两项，选项可不传
        if ($type !== 'judge') {
            $options = json_decode($this->cell($row, 3), true);

            if (! is_array($options)) {
                return '选项JSON格式错误';
            }

            if (count($options) < 2) {
                return '选项至少需要2个';
            }

            if (count($options) > 6) {
                return '选项最多6个';
            }

            foreach ($options as $option) {
                if (! is_array($option) || ! isset($option['key'], $option['content'])) {
                    return '选项JSON格式错误';
                }
            }
        }

        $answer = str_replace(' ', '', $this->cell($row, 4));

        $valid = match ($type) {
            'single' => (bool) preg_match('/^[A-F]$/', $answer),
            'multiple' => (bool) preg_match('/^[A-F](,[A-F])+$/', $answer),
            'judge' => in_array($answer, ['正确', '错误'], true),
        };

        if (! $valid) {
            return '正确答案格式错误';
        }

        $analysis = $this->cell($row, 5);

        if (mb_strlen($analysis) > 500) {
            return '答案解析超过500字';
        }

        return null;
    }

    /**
     * 组装 question 表数据（判断题补默认选项）
     *
     * @param  array<int, string>  $row
     * @return array<string, mixed>
     */
    private function buildQuestionData(array $row, int $adminId): array
    {
        $type = $this->cell($row, 1);

        if ($type === 'judge') {
            $options = [
                ['key' => '正确', 'content' => '正确'],
                ['key' => '错误', 'content' => '错误'],
            ];
            $optionCount = 2;
        } else {
            $options = json_decode($this->cell($row, 3), true) ?: [];
            $optionCount = count($options);
        }

        return [
            'type' => $type,
            'stem' => $this->cell($row, 2),
            'options' => $options,
            'option_count' => $optionCount,
            'correct_answer' => str_replace(' ', '', $this->cell($row, 4)),
            'analysis' => $this->cell($row, 5) ?: null,
            'difficulty' => 1,
            'knowledge_point' => '',
            'created_by' => $adminId,
        ];
    }

    /**
     * 任务列表项字段映射（2.8.1 / 2.8.2 共用）
     *
     * @return array<string, mixed>
     */
    private function mapTask(ImportTask $task): array
    {
        return [
            'id' => (int) $task->id,
            'task_no' => $task->task_no,
            'file_name' => $task->file_name,
            'total_rows' => (int) $task->total_rows,
            'valid_rows' => (int) $task->valid_rows,
            'error_rows' => (int) $task->error_rows,
            'status' => (int) $task->status,
            'created_at' => $task->created_at?->format('Y-m-d H:i:s'),
            'completed_at' => $task->completed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
