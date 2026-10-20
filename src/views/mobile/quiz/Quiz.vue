<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import QuizPanel from '@/components/mobile/QuizPanel.vue'
import { toast } from '@/utils/toast'
import { gradeQuiz } from '@/utils/question'
import {
  PASS_PERCENT,
  WATCH_PERCENT,
  ensureLearner,
  getCourse,
  getProgress,
  listQuestions,
  newId,
  saveProgress,
  saveRecord,
} from '@/store'

const route = useRoute()
const router = useRouter()
const courseId = String(route.query.courseId || '')
const stage = String(route.query.stage) === 'post' ? 'post' : 'pre'

const course = ref(null)
const learner = ref(null)
const questions = ref([])
const ready = ref(false)
const answeredCount = ref(0)
const startedAt = ref(Date.now())
const submitting = ref(false)

const isPre = computed(() => stage === 'pre')
const stageTitle = computed(() => (isPre.value ? '学前答题' : '看完答题'))

onMounted(() => {
  learner.value = ensureLearner()
  course.value = getCourse(courseId)
  if (!courseId || !course.value) {
    router.replace('/mobile/home')
    return
  }

  questions.value = listQuestions(courseId, stage)
  if (!questions.value.length) {
    ready.value = true
    if (!isPre.value) router.replace({ name: 'mobile-learn', query: { courseId } })
    return
  }

  const progress = getProgress(learner.value.userId, courseId)
  if (!isPre.value) {
    const preList = listQuestions(courseId, 'pre')
    if (preList.length && !progress.preDone) {
      toast('请先完成学前答题')
      router.replace({ name: 'mobile-quiz', query: { courseId, stage: 'pre' } })
      return
    }
    if (course.value.videoUrl && (progress.watchedPercent || 0) < WATCH_PERCENT) {
      toast('请先完整观看视频再来答题')
      router.replace({ name: 'mobile-learn', query: { courseId } })
      return
    }
    if (progress.postDone) toast('这组题您答过了，可以再答一次')
  }

  startedAt.value = Date.now()
  ready.value = true
})

function onSubmit(answers) {
  if (submitting.value) return
  submitting.value = true
  const result = gradeQuiz(questions.value, answers, PASS_PERCENT)
  const userId = learner.value.userId
  const progress = getProgress(userId, courseId)

  if (isPre.value) {
    saveProgress(userId, courseId, {
      ...progress,
      preDone: true,
      prePercent: result.percent,
      preCorrect: result.correct,
      preTotal: result.total,
      preDetail: result.detail,
    })
    toast(`前测完成，答对 ${result.correct}/${result.total} 题`)
    router.replace({ name: 'mobile-learn', query: { courseId } })
    return
  }

  const record = {
    id: newId('r_'),
    userId,
    courseId,
    courseTitle: course.value.title,
    preCorrect: progress.preCorrect || 0,
    preTotal: progress.preTotal || 0,
    prePercent: progress.prePercent || 0,
    postCorrect: result.correct,
    postTotal: result.total,
    percent: result.percent,
    passed: result.passed,
    detail: [...(progress.preDetail || []), ...result.detail],
    startedAt: startedAt.value,
    completedAt: Date.now(),
  }
  saveRecord(record)
  saveProgress(userId, courseId, {
    ...progress,
    postDone: true,
    attempts: (progress.attempts || 0) + 1,
    bestPercent: Math.max(progress.bestPercent || 0, result.percent),
    passed: result.passed || progress.passed,
    lastRecordId: record.id,
  })
  router.replace({ name: 'mobile-report', query: { recordId: record.id } })
}
</script>

<template>
  <div class="qp">
    <PageNav :title="stageTitle">
      <template #right>
        <span class="qp__progress">{{ answeredCount }}/{{ questions.length }}</span>
      </template>
    </PageNav>

    <div v-if="course" class="qp__head" :class="isPre ? 'qp__head--pre' : 'qp__head--post'">
      <AppIcon :name="isPre ? 'info' : 'shield'" :size="17" class="qp__head-icon" />
      <div>
        <b>{{ isPre ? '先答 3 道题，看看您的基础认知' : '再看 3 道题，检验一下有没有记住' }}</b>
        <span>《{{ course.title }}》· 提交后可以看到每道题的对错与解析</span>
      </div>
    </div>

    <QuizPanel
      v-if="questions.length"
      :questions="questions"
      :stage-label="stageTitle"
      @submit="onSubmit"
      @change="answeredCount = $event"
    />

    <div v-else-if="ready" class="empty">
      <div class="empty__icon">
        <AppIcon name="inbox" :size="44" />
      </div>
      <p class="empty__text">这条视频还没有配置{{ stageTitle }}题目</p>
      <div class="empty__actions">
        <button type="button" class="btn btn--primary btn--md" @click="router.replace('/mobile/home')">
          返回视频列表
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.qp {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg-page);
  padding-bottom: var(--sp-8);
  margin: calc(-1 * var(--sp-4));
  margin-top: calc(-1 * var(--sp-4));
  margin-left: calc(-1 * max(var(--sp-4), env(safe-area-inset-left, 0px)));
  margin-right: calc(-1 * max(var(--sp-4), env(safe-area-inset-right, 0px)));
}

.qp__progress {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
  padding-right: var(--sp-2);
  white-space: nowrap;
}

.qp__head {
  display: flex;
  align-items: flex-start;
  gap: var(--sp-2);
  margin: var(--sp-4) var(--sp-4) 0;
  padding: var(--sp-3) var(--sp-4);
  border-radius: var(--r-lg);
  font-size: var(--fs-xs);
  line-height: 1.6;
  word-break: break-word;
}

.qp__head b {
  display: block;
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
}

.qp__head span {
  display: block;
  margin-top: 2px;
  opacity: 0.85;
}

.qp__head--pre {
  background: var(--info-bg);
  color: var(--info-text);
}

.qp__head--post {
  background: var(--warning-bg);
  color: var(--warning-text);
}

.qp__head-icon {
  margin-top: 1px;
  flex-shrink: 0;
}
</style>
