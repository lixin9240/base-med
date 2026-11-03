<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import QuizPanel from '@/components/mobile/QuizPanel.vue'
import QuizSplash from '@/components/mobile/QuizSplash.vue'
import { speak } from '@/utils/speech'
import { toast } from '@/utils/toast'
import { currentDrId, isSkipWatch, issueDrId } from '@/store/mobile/session'
import {
  getPublicVideoDetail,
  listPublicQuestions,
  submitPostExam,
  submitPreExam,
  submitRetakeExam,
} from '@/api/public'

const route = useRoute()
const router = useRouter()

function queryText(source, key) {
  const value = source?.[key]
  if (Array.isArray(value)) return value[0] ? String(value[0]) : ''
  return value ? String(value) : ''
}

/** 分享链接可能先落到首页再跳回答题，地址栏参数比首次路由更可靠 */
function entryVideoId() {
  const fromRoute = queryText(route.query, 'videoId') || queryText(route.query, 'video_id')
  if (fromRoute) return fromRoute
  const search = new URLSearchParams(window.location.search)
  return search.get('videoId') || search.get('video_id') || ''
}

// 分享链接（后端生成）形如 /mobile/quiz?videoId=5&share=xxx，兼容 video_id 写法
let videoId = entryVideoId()
const stageParam = String(route.query.stage || '')
const isRetake = stageParam === 'retake'
/**
 * 只有显式带 stage=post / stage=retake 才进入后测（看完答题）；
 * 其余情况（分享链接入口、参数丢失）一律从「学前答题」开始。
 * 页面内跳转（首页 / 学习页 / 报告页）都会显式带上 stage。
 */
const stage = computed(() =>
  stageParam === 'post' || stageParam === 'retake' ? 'post' : 'pre',
)

const course = ref(null)
const drId = ref('')
const questions = ref([])
const ready = ref(false)
const error = ref('')
const answeredCount = ref(0)
const submitting = ref(false)
/** 本次进入是否正在播开屏。学前、再次答题会播；看完视频后的那次不播 */
const showSplash = ref(false)

// TODO: 后端暂未返回答题/中奖人数统计，先使用占位数据
const answeredPeople = 1286
const winnerPeople = 357

const isPre = computed(() => stage.value === 'pre')
const stageTitle = computed(() =>
  isRetake ? '再次答题' : isPre.value ? '学前答题' : '看完答题',
)
const splashCue = computed(() => {
  const title = course.value?.title
  if (!isPre.value || !title) return ''
  return `即将开始《${title}》的学前答题`
})

function categoryIcon(category) {
  if (category === '影像检查') return 'monitor'
  if (category === '急救技能') return 'heartPulse'
  if (category === '用药安全') return 'activity'
  return 'stethoscope'
}

function categoryTone(category) {
  if (category === '影像检查') return 'teal'
  if (category === '妇幼保健' || category === '慢病管理') return 'pink'
  if (category === '用药安全') return 'lavender'
  return 'mint'
}

watch(error, (value) => {
  if (value) showSplash.value = false
})

function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function armSplash() {
  if (prefersReducedMotion()) return
  showSplash.value = true
}

function onSplashFinished() {
  showSplash.value = false
}

/** 确认会留在答题页后再开屏。看完视频后的那次答题直接出题 */
function revealQuiz() {
  if (isPre.value || isRetake) armSplash()
  ready.value = true
}

onMounted(async () => {
  try {
    videoId = entryVideoId() || videoId
    drId.value = currentDrId()
    // 分享链接入口：直接打开 /mobile/quiz?videoId=X 时无 dr_id，先签发匿名编号再答题
    if (!drId.value && videoId) {
      drId.value = await issueDrId(videoId)
    }
    if (!videoId || !drId.value) {
      error.value = !videoId ? '链接缺少视频编号' : '进入失败，请重新打开链接'
      return
    }
    const detail = await getPublicVideoDetail(drId.value, videoId)
    course.value = {
      id: detail.id,
      title: detail.title,
      category: detail.category_name,
      cover: detail.cover_url,
      videoUrl: detail.video_url,
      examState: detail.exam_state,
      watchProgress: detail.watch_progress,
    }
    questions.value = await listPublicQuestions(videoId)

    if (!questions.value.length) {
      if (!isPre.value) {
        router.replace({ name: 'mobile-learn', query: { videoId } })
        return
      }
      revealQuiz()
      return
    }

    // 再次答题（3.9）不校验学前答题与观看进度：用户已完整走过一遍
    if (!isPre.value && !isRetake) {
      if (!course.value.examState?.pre_submitted) {
        toast('请先完成学前答题')
        router.replace({ name: 'mobile-quiz', query: { videoId, stage: 'pre' } })
        revealQuiz()
        return
      }
      if (course.value.videoUrl && !isSkipWatch(videoId) && course.value.watchProgress?.is_unlocked !== 1) {
        toast('请先完整观看视频再来答题')
        router.replace({ name: 'mobile-learn', query: { videoId } })
        return
      }
    }

    revealQuiz()
  } catch (e) {
    error.value = e?.message || '加载失败'
  }
})

function toApiAnswer(question, answer) {
  if (question.type === 'multiple') {
    return Array.isArray(answer) ? [...answer].sort().join(',') : ''
  }
  return answer ?? ''
}

async function onSubmit(answers) {
  if (submitting.value) return
  submitting.value = true
  try {
    if (isPre.value) {
      await submitPreExam({
        dr_id: drId.value,
        video_id: videoId,
        answers: questions.value.map((q, i) => ({
          question_id: q.question_id,
          user_answer: toApiAnswer(q, answers[i]),
        })),
      })
      toast('前测完成')
      speak(`学前答题结束。请观看《${course.value?.title || '本视频'}》`)
      router.replace({ name: 'mobile-learn', query: { videoId } })
      return
    }

    // 再次答题走 3.9：只重评后测，前测沿用最近一条记录
    const submit = isRetake ? submitRetakeExam : submitPostExam
    const data = await submit({
      dr_id: drId.value,
      video_id: videoId,
      answers: questions.value.map((q, i) => ({
        question_id: q.question_id,
        user_answer: toApiAnswer(q, answers[i]),
      })),
    })
    router.replace({ name: 'mobile-report', query: { examRecordId: data.exam_record_id, videoId: data.video_id } })
  } catch (e) {
    toast(e?.message || '提交失败')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="qp">
    <PageNav
      :title="stageTitle"
      :show-back="false"
      :answered-people="answeredPeople"
      :winner-people="winnerPeople"
    >
    </PageNav>

    <div v-if="error" class="empty">
      <div class="empty__icon">
        <AppIcon name="alert" :size="44" />
      </div>
      <p class="empty__text">{{ error }}</p>
      <div class="empty__actions">
        <button type="button" class="btn btn--primary btn--md" @click="router.replace('/mobile/home')">
          返回视频列表
        </button>
      </div>
    </div>

    <template v-else>
      <div v-if="course" class="qp__cover">
        <img v-if="course.cover" :src="course.cover" :alt="course.title" />
        <span v-else class="vcard__thumb-ph qp__cover-ph" :class="`vcard__thumb-ph--${categoryTone(course.category)}`">
          <AppIcon :name="categoryIcon(course.category)" :size="30" />
        </span>
      </div>

      <div v-if="course" class="qp__head" :class="isPre ? 'qp__head--pre' : 'qp__head--post'">
        <AppIcon :name="isPre ? 'info' : 'shield'" :size="17" class="qp__head-icon" />
        <div>
          <b>{{
            isRetake
              ? '再答一遍后测题目，看看这次能不能提分'
              : isPre
                ? '先答 3 道题，看看您的基础认知'
                : '再看 3 道题，检验一下有没有记住'
          }}</b>
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
    </template>

    <QuizSplash v-if="showSplash" :can-leave="ready" :cue="splashCue" @finished="onSplashFinished" />
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

.qp__cover {
  width: calc(100% - var(--sp-4) * 2);
  aspect-ratio: 16 / 9;
  border-radius: var(--r-xl);
  overflow: hidden;
  margin: var(--sp-3) var(--sp-4) 0;
  box-shadow: var(--shadow-sm);
  background: var(--brand-tint);
}

.qp__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.qp__cover-ph {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
