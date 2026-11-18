<?php

namespace Tests\Unit;

use App\Models\Video;
use App\Services\Fmy\VideoService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * 内置固定题「是否为医护人员」单元测试（不依赖数据库）
 */
class MedicalStaffQuestionTest extends TestCase
{
    /**
     * 构造注入了固定真实题目的 VideoService 替身
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @return VideoService&MockObject
     */
    private function serviceWithQuestions(array $questions): VideoService
    {
        $service = $this->getMockBuilder(VideoService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['questionSet', 'onShelfVideo'])
            ->getMock();

        $service->method('questionSet')->willReturn(collect($questions));
        $service->method('onShelfVideo')->willReturn(new Video());

        return $service;
    }

    /**
     * 两条真实题目
     *
     * @return array<int, array<string, mixed>>
     */
    private function realQuestions(): array
    {
        return [
            [
                'question_id' => 101,
                'type' => 'single',
                'stem' => '真实题一',
                'image_url' => '',
                'options' => [
                    ['key' => 'A', 'content' => '选项一'],
                    ['key' => 'B', 'content' => '选项二'],
                ],
                'correct_answer' => 'A',
                'analysis' => '解析一',
            ],
            [
                'question_id' => 102,
                'type' => 'single',
                'stem' => '真实题二',
                'image_url' => '',
                'options' => [
                    ['key' => 'A', 'content' => '选项一'],
                    ['key' => 'B', 'content' => '选项二'],
                ],
                'correct_answer' => 'B',
                'analysis' => '解析二',
            ],
        ];
    }

    public function test_medical_staff_question_is_fixed_single_choice(): void
    {
        $question = (new VideoService())->medicalStaffQuestion();

        $this->assertSame(VideoService::MEDICAL_STAFF_QUESTION_ID, $question['question_id']);
        $this->assertSame(0, $question['question_id']);
        $this->assertSame('single', $question['type']);
        $this->assertSame('请问您是否为医护人员？', $question['stem']);
        $this->assertSame(['A', 'B'], array_column($question['options'], 'key'));
        $this->assertSame(['是', '否'], array_column($question['options'], 'content'));
        $this->assertNull($question['correct_answer']);
        $this->assertSame(1, $question['is_medical_staff']);
    }

    public function test_pretest_question_set_prepends_fixed_question(): void
    {
        $service = $this->serviceWithQuestions($this->realQuestions());

        $questions = $service->examQuestionSet(1, 'pretest');

        $this->assertCount(3, $questions);
        $this->assertSame(0, $questions->first()['question_id']);
        $this->assertSame([0, 101, 102], $questions->pluck('question_id')->all());
    }

    public function test_posttest_question_set_excludes_fixed_question(): void
    {
        $service = $this->serviceWithQuestions($this->realQuestions());

        $questions = $service->examQuestionSet(1, 'posttest');

        $this->assertCount(2, $questions);
        $this->assertSame([101, 102], $questions->pluck('question_id')->all());
    }

    public function test_question_set_without_stage_excludes_fixed_question(): void
    {
        $service = $this->serviceWithQuestions($this->realQuestions());

        $questions = $service->examQuestionSet(1);

        $this->assertCount(2, $questions);
        $this->assertSame([101, 102], $questions->pluck('question_id')->all());
    }

    public function test_extract_medical_staff_maps_option_a_to_yes(): void
    {
        $service = new VideoService();

        $this->assertSame(1, $service->extractMedicalStaff([
            ['question_id' => 0, 'user_answer' => 'A'],
        ]));
        $this->assertSame(1, $service->extractMedicalStaff([
            ['question_id' => 0, 'user_answer' => 'a'],
        ]));
    }

    public function test_extract_medical_staff_maps_option_b_to_no(): void
    {
        $service = new VideoService();

        $this->assertSame(0, $service->extractMedicalStaff([
            ['question_id' => 0, 'user_answer' => 'B'],
        ]));
        $this->assertSame(0, $service->extractMedicalStaff([
            ['question_id' => 0, 'user_answer' => 'b'],
        ]));
    }

    public function test_extract_medical_staff_returns_null_when_absent(): void
    {
        $service = new VideoService();

        $this->assertNull($service->extractMedicalStaff([]));
        $this->assertNull($service->extractMedicalStaff([
            ['question_id' => 101, 'user_answer' => 'A'],
        ]));
    }

    public function test_question_list_pretest_returns_fixed_question_first(): void
    {
        $service = $this->serviceWithQuestions($this->realQuestions());

        $list = $service->questionList(1, 'pretest');

        $this->assertCount(3, $list);
        $this->assertSame(0, $list[0]['question_id']);
        $this->assertSame(1, $list[0]['is_medical_staff']);
        $this->assertSame(0, $list[1]['is_medical_staff']);
        // 不泄露正确答案
        $this->assertArrayNotHasKey('correct_answer', $list[0]);
        $this->assertArrayNotHasKey('analysis', $list[0]);
    }

    public function test_question_list_posttest_returns_only_real_questions(): void
    {
        $service = $this->serviceWithQuestions($this->realQuestions());

        $list = $service->questionList(1, 'posttest');

        $this->assertCount(2, $list);
        $this->assertSame([101, 102], array_column($list, 'question_id'));
        $this->assertSame([0, 0], array_column($list, 'is_medical_staff'));
    }
}
