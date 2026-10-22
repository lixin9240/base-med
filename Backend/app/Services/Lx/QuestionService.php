<?php

namespace App\Services\Lx;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Question;
use App\Models\VideoQuestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuestionService
{
    /**
     * 题目列表
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator
     */
    public function list(array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $query = Question::query();

        if (! empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (! empty($params['knowledge_point'])) {
            $query->where('knowledge_point', $params['knowledge_point']);
        }

        if (! empty($params['stem'])) {
            $query->where('stem', 'like', '%' . $params['stem'] . '%');
        }

        $paginator = $query->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Question $question) => [
                'id' => $question->id,
                'type' => $question->type,
                'stem' => $question->stem,
                'option_count' => $question->option_count,
                'difficulty' => $question->difficulty,
                'knowledge_point' => $question->knowledge_point,
                'created_at' => $question->created_at?->format('Y-m-d H:i:s'),
            ])->values()
        );

        return $paginator;
    }

    /**
     * 编辑题目（题库维度，不影响 video_question 绑定）
     *
     * @param  array<string, mixed>  $params
     * @return int
     */
    public function update(array $params): int
    {
        $question = Question::find($params['id']);

        if (! $question) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '题目不存在');
        }

        $type = $params['type'];

        $data = [
            'type' => $type,
            'stem' => $params['stem'],
            'correct_answer' => Question::normalizeCorrectAnswer($type, (string) $params['correct_answer']),
            'analysis' => $params['analysis'] ?? null,
            'image_url' => $params['image_url'] ?? null,
            'difficulty' => $params['difficulty'] ?? $question->difficulty,
            'knowledge_point' => $params['knowledge_point'] ?? null,
        ];

        if ($type === 'judge') {
            $data['option_count'] = 2;
        } else {
            $options = $params['options'] ?? [];

            if (count($options) < 2) {
                throw new BusinessException(ResponseCode::PARAM_ERROR, '选项至少需要 2 个');
            }

            $data['options'] = $options;
            $data['option_count'] = count($options);
        }

        $question->update($data);

        return $question->id;
    }

    /**
     * 删除题目（软删除，被视频引用时禁止）
     *
     * @param  int  $id
     * @return void
     */
    public function delete(int $id): void
    {
        $question = Question::find($id);

        if (! $question) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '题目不存在');
        }

        if (VideoQuestion::where('question_id', $id)->exists()) {
            throw new BusinessException(ResponseCode::DATA_RELATION_EXISTS, '题目已被视频引用，请先移除关联后再删除');
        }

        $question->delete();
    }
}