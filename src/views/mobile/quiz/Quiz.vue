<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import QuizPanel from '@/components/mobile/QuizPanel.vue'
import { toast } from '@/utils/toast'
import { WATCH_PERCENT, getProgress } from '@/store'
import { resolveDrId } from '@/store/mobile/session'
import { getPublicVideoDetail, listPublicQuestions, submitPreExam } from '@/api/public'

const route = useRoute()
const router = useRouter()
const videoId = String(route.query.videoId || '')
const stage = String(route.query.stage) === 'post' ? 'post' : 'pre'

const course = ref(null)
const drId = ref('')
const questions = ref([])
const ready = ref(false)
const error = ref('')
const answeredCount = ref(0)
const submitting = ref(false)

const isPre = computed(() => stage === 'pre')
const stageTitle = computed(() => (isPre.value ? '学前答题' : '看完答题'))

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

onMounted(async () => {
  try {
    if (!videoId) {
      router.replace('/mobile/home')
      return
    }
    drId.value = await resolveDrId()
    const detail = await getPublicVideoDetail(drId.value, videoId)
    course.value = {
      id: detail.id,
      title: detail.title,
      category: detail.category_name,
      cover: detail.cover_url,
      videoUrl: detail.video_url,
      examState: detail.exam_state,
    }
    questions.value = await listPublicQuestions(videoId)

    if (!questions.value.length) {
      ready.value = true
      if (!isPre.value) router.replace({ name: 'mobile-learn', query: { videoId } })
      return
    }

    if (!isPre.value) {
      if (!course.value.examState?.pre_submitted) {
        toast('请先完成学前答题')
        router.replace({ name: 'mobile-quiz', query: { videoId, stage: 'pre' } })
        return
      }
      if (course.value.videoUrl) {
        const progress = getProgress(drId.value, videoId)
        if ((progress.watchedPercent || 0) < WATCH_PERCENT) {
          toast('请先完整观看视频再来答题')
          router.replace({ name: 'mobile-learn', query: { videoId } })
          return
        }
      }
    }

    ready.value = true
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
        video_id: Number(videoId),
        answers: questions.value.map((q, i) => ({
          question_id: Number(q.question_id),
          user_answer: toApiAnswer(q, answers[i]),
        })),
      })
      toast('前测完成')
      router.replace({ name: 'mobile-learn', query: { videoId } })
      return
    }

    // TODO: 接口文档未定义「提交看完答题」（submit-post），后端就绪后在此调用并携带 exam_record_id 跳结果页
    router.replace({ name: 'mobile-report', query: { videoId } })
  } catch (e) {
    toast(e?.message || '提交失败')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="qp">
    <PageNav :title="stageTitle">
      <template #right>
        <span class="qp__progress">{{ answeredCount }}/{{ questions.length }}</span>
      </template>
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
    </template>
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
