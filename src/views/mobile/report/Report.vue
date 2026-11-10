<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import { QUESTION_TYPE_LABEL } from '@/utils/question'
import { speak, whenSpeechEnds } from '@/utils/speech'
import { currentDrId } from '@/store/mobile/session'
import { getExamResult } from '@/api/public'

const route = useRoute()
const router = useRouter()
const record = ref(null)
const error = ref('')
const loading = ref(true)
// TODO: 后端暂未返回答题/中奖人数统计，先使用占位数据
const answeredPeople = 1286
const winnerPeople = 357
// 顶栏语音胶囊状态：idle 不展示 / playing 播放中 / done 已播放
const speechState = ref('idle')

const percentValue = computed(() => {
  const value = Number(record.value?.percent)
  return Number.isFinite(value) ? Math.min(100, Math.max(0, value)) : 0
})

const wrongCount = computed(() => {
  const detail = record.value?.detail || []
  return detail.filter((item) => !item.correct).length
})

function mapDetailItem(item) {
  return {
    questionId: item.question_id,
    type: item.type,
    stem: item.stem,
    options: item.options || [],
    answer: item.correct_answer,
    userAnswer: item.post_user_answer,
    correct: item.post_is_correct === 1 || item.post_is_correct === true,
    analysis: item.analysis || '',
  }
}

onMounted(async () => {
  const videoId = String(route.query.videoId || '')
  const examRecordId = String(route.query.examRecordId || '')
  if (!videoId && !examRecordId) {
    router.replace('/mobile/home')
    return
  }
  try {
    const drId = currentDrId()
    if (!drId) {
      router.replace('/mobile/home')
      return
    }
    const data = await getExamResult({
      dr_id: drId,
      ...(examRecordId ? { exam_record_id: examRecordId } : { video_id: videoId }),
    })
    record.value = {
      examRecordId: data.exam_record_id,
      videoId: data.video_id,
      courseTitle: data.video_title,
      passed: data.result_status === 'pass',
      passPercent: Number(data.pass_accuracy) || 0,
      percent: data.accuracy_rate,
      preCorrect: data.pre?.correct ?? 0,
      preTotal: data.pre?.total ?? 0,
      postCorrect: data.post?.correct ?? 0,
      postTotal: data.post?.total ?? 0,
      // TODO: 后端结果接口暂未返回这两项统计，先使用占位数据
      answeredCount: 128,
      winnerCount: 36,
      detail: (data.question_detail || []).map(mapDetailItem),
    }
    const percent = Number(record.value.percent)
    if (Number.isFinite(percent)) {
      speak(`答题结束。正确率百分之${percent}，${record.value.passed ? '已通过' : '未通过'}`)
    }
  } catch (e) {
    error.value = e?.message || '加载失败'
  } finally {
    loading.value = false
  }
})

function optionToken(type, opt) {
  return type === 'judge' ? opt.content : opt.key
}

// 判断题在库里存的是「正确 / 错误」，展示时统一换成 √ / ×
function judgeMark(value) {
  const text = String(value ?? '').trim()
  if (!text) return ''
  if (['√', '正确', '对', 'true', '1'].includes(text)) return '√'
  if (['×', '错误', '错', 'false', '0'].includes(text)) return '×'
  return text
}

function optionKeyLabel(item, opt) {
  if (item.type === 'judge') return judgeMark(opt.content ?? opt.key)
  return opt.key
}

function isRight(item, opt) {
  const token = optionToken(item.type, opt)
  if (item.type === 'multiple') {
    return String(item.answer || '').split(',').map((s) => s.trim()).includes(token)
  }
  return item.answer === token
}

function isPicked(item, opt) {
  const token = optionToken(item.type, opt)
  if (item.type === 'multiple') {
    return String(item.userAnswer || '').split(',').map((s) => s.trim()).includes(token)
  }
  return item.userAnswer === token
}

// 多选题：选项有「是否为正确答案」「是否被用户选中」两个独立维度，四种组合分别标注
function optionTag(item, opt) {
  const right = isRight(item, opt)
  const picked = isPicked(item, opt)
  if (item.type !== 'multiple') {
    if (right) return '正确答案'
    if (picked) return '您的选择'
    return ''
  }
  if (right && picked) return '您选择了 · 正确'
  if (right) return '正确答案 · 您未选'
  if (picked) return '您的选择 · 错误'
  return ''
}

// 正确答案展示文案：判断题换成 √ / ×，多选用「、」连接，单选/判断直接展示
function correctAnswerLabel(item) {
  if (item.type === 'judge') return judgeMark(item.answer)
  if (item.type === 'multiple') {
    return String(item.answer || '')
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
      .join('、')
  }
  return item.answer || ''
}

function goLottery() {
  router.push({
    name: 'mobile-lottery',
    query: {
      videoId: record.value?.videoId,
      examRecordId: record.value?.examRecordId,
    },
  })
}
</script>

<template>
  <div class="result">
    <PageNav
      title="答题结果"
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
    </div>

    <div v-else-if="loading" class="empty">
      <p class="empty__text">加载中…</p>
    </div>

    <template v-else-if="record">
      <section class="hero rise" :class="record.passed ? 'hero--pass' : 'hero--fail'">
        <p class="hero__eyebrow">看完答题 · 共 {{ record.postTotal }} 题</p>
        <h1 class="hero__title">{{ record.passed ? '答得不错！' : '再复习一下？' }}</h1>
        <p class="hero__sub">
          《{{ record.courseTitle }}》·
          {{ record.passed ? `已达到 ${record.passPercent}% 通过线` : `答对 ${record.passPercent}% 以上才算通过` }}
        </p>

        <div class="hero__score">
          <span class="hero__num">{{ record.postCorrect }}</span>
          <span class="hero__total">/ {{ record.postTotal }} 题</span>
          <span class="hero__badge" :class="record.passed ? 'hero__badge--pass' : 'hero__badge--fail'">
            {{ record.passed ? '已通过' : '未通过' }}
          </span>
        </div>

        <div class="bar">
          <div class="bar__meta">
            <span>正确率 <b>{{ percentValue }}%</b></span>
            <span>通过线 {{ record.passPercent }}%</span>
          </div>
          <div class="bar__track">
            <div class="bar__fill" :style="{ '--p': `${percentValue}%` }" />
            <div class="bar__mark" :style="{ left: `${record.passPercent}%` }">
              <span>{{ record.passPercent }}%</span>
            </div>
          </div>
        </div>

        <div v-if="record.preTotal" class="compare">
          <span class="pill">学前答题 <b>{{ record.preCorrect }}/{{ record.preTotal }}</b></span>
          <span class="compare__arrow">
            <AppIcon name="arrowRight" :size="14" />
          </span>
          <span class="pill" :class="record.passed ? 'pill--pass' : 'pill--now'">
            看完答题 <b>{{ record.postCorrect }}/{{ record.postTotal }}</b>
          </span>
        </div>
      </section>

      <!-- <section class="lottery-entry">
        <button type="button" class="btn btn--primary btn--lg btn--block" @click="goLottery">
          参与抽奖
        </button>
      </section> -->

      <section class="review">
        <div class="section-head">
          <h2>逐题解析</h2>
          <span>{{ record.postTotal }} 题 · 答错 {{ wrongCount }} 题</span>
        </div>

        <article
          v-for="(item, index) in record.detail"
          :key="item.questionId || index"
          class="qcard rise"
          :style="{ '--d': `${index * 30}ms` }"
        >
          <div class="qcard__head">
            <span class="qnum">{{ index + 1 }}</span>
            <span class="chip" :class="item.correct ? 'chip--good' : 'chip--bad'">
              <span class="chip__text">{{ item.correct ? '答对' : '答错' }}</span>
            </span>
            <span class="qcard__type">{{ QUESTION_TYPE_LABEL[item.type] }}</span>
          </div>

          <p class="stem">{{ item.stem }}</p>

          <ul class="opts">
            <li
              v-for="(opt, i) in item.options"
              :key="i"
              class="opt"
              :class="{ 'is-right': isRight(item, opt), 'is-wrong': isPicked(item, opt) && !isRight(item, opt) }"
            >
              <span class="opt__key">{{ optionKeyLabel(item, opt) }}</span>
              <span class="opt__text">{{ opt.content }}</span>
              <span v-if="optionTag(item, opt)" class="opt__tag">{{ optionTag(item, opt) }}</span>
            </li>
          </ul>

          <p class="answer"><b>正确答案：</b>{{ correctAnswerLabel(item) }}</p>

          <div v-if="item.analysis" class="note">
            <span class="note__tag">解析</span>
            <span>{{ item.analysis }}</span>
          </div>
        </article>
      </section>
    </template>

    <div v-else class="empty">
      <div class="empty__icon">
        <AppIcon name="inbox" :size="44" />
      </div>
      <p class="empty__text">未找到这次答题记录</p>
    </div>
  </div>
</template>

<style scoped>
.result {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg-page);
  padding-bottom: calc(var(--sp-10) + env(safe-area-inset-bottom, 0px));
  margin: calc(-1 * var(--sp-4));
  margin-top: calc(-1 * var(--sp-4));
  margin-left: calc(-1 * max(var(--sp-4), env(safe-area-inset-left, 0px)));
  margin-right: calc(-1 * max(var(--sp-4), env(safe-area-inset-right, 0px)));
}

/* 顶栏语音胶囊 */
.voice {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 24px;
  padding: 0 9px;
  border-radius: var(--r-full);
  background: var(--brand-primary);
  color: #fff;
  font-size: 0.6875em;
  white-space: nowrap;
}

.voice::before {
  content: "";
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #f5c451;
}

/* ── 成绩卡 ─────────────────────────────────────────── */
.hero {
  position: relative;
  overflow: hidden;
  margin: var(--sp-4);
  padding: var(--sp-5) var(--sp-4) var(--sp-4);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
}

.hero--fail {
  background: linear-gradient(160deg, var(--warning-bg) 0%, var(--bg-surface-solid) 78%);
}

.hero--pass {
  background: linear-gradient(160deg, var(--success-bg) 0%, var(--bg-surface-solid) 78%);
}

/* 右上角柔光，避免 0 分卡片显得死板 */
.hero::after {
  content: "";
  position: absolute;
  top: -70px;
  right: -50px;
  width: 190px;
  height: 190px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--brand-glow), transparent 68%);
  opacity: 0.35;
  pointer-events: none;
}

.hero__eyebrow {
  position: relative;
  margin: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 22px;
  padding: 0 9px;
  border-radius: var(--r-full);
  background: var(--brand-soft);
  color: var(--brand-soft-text);
  font-size: 0.6875em;
  font-weight: var(--fw-semibold);
}

.hero__title {
  position: relative;
  margin: var(--sp-3) 0 0;
  font-size: 1.1875em;
  font-weight: var(--fw-bold);
  letter-spacing: 0.01em;
}

.hero__sub {
  position: relative;
  margin: 5px 0 0;
  font-size: var(--fs-xs);
  line-height: 1.6;
  color: var(--text-secondary);
}

.hero__score {
  position: relative;
  display: flex;
  align-items: flex-end;
  gap: var(--sp-3);
  margin-top: var(--sp-4);
}

.hero__num {
  font-size: 2.75em;
  font-weight: var(--fw-bold);
  line-height: 0.95;
  letter-spacing: -0.02em;
  font-variant-numeric: tabular-nums;
}

.hero__total {
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  padding-bottom: 3px;
}

.hero__badge {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 26px;
  padding: 0 10px;
  border-radius: var(--r-full);
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
}

.hero__badge::before {
  content: "";
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}

.hero__badge--fail {
  background: var(--danger-bg);
  color: var(--danger-text);
}

.hero__badge--pass {
  background: var(--success-bg);
  color: var(--success-text);
}

/* 进度条 + 通过线刻度 */
.bar {
  position: relative;
  margin-top: var(--sp-4);
}

.bar__meta {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 7px;
  font-size: 0.6875em;
  color: var(--text-secondary);
}

.bar__meta b {
  font-size: 1.1em;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}

.bar__track {
  position: relative;
  height: 8px;
  border-radius: var(--r-full);
  background: var(--bg-muted);
}

.bar__fill {
  height: 100%;
  width: 0;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--brand-primary-hover), var(--brand-primary));
  animation: grow 0.9s cubic-bezier(0.22, 0.8, 0.25, 1) 0.15s forwards;
}

.bar__mark {
  position: absolute;
  top: -3px;
  bottom: -3px;
  width: 0;
  border-left: 1px dashed var(--warning-text);
}

.bar__mark span {
  position: absolute;
  bottom: calc(100% + 5px);
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.625em;
  color: var(--warning-text);
  white-space: nowrap;
}

/* 学前 / 看完 对比 */
.compare {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  margin-top: var(--sp-5);
  padding-top: var(--sp-3);
  border-top: 1px solid var(--border);
  font-size: var(--fs-xs);
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: var(--r-full);
  background: var(--brand-tint);
  color: var(--text-secondary);
  white-space: nowrap;
}

.pill b {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}

.pill--now {
  background: var(--danger-bg);
}

.pill--now b {
  color: var(--danger-text);
}

.pill--pass {
  background: var(--success-bg);
}

.pill--pass b {
  color: var(--success-text);
}

.compare__arrow {
  display: inline-flex;
  color: var(--text-disabled);
}

/* ── 逐题解析 ────────────────────────────────────────── */
.review {
  margin: 0 var(--sp-4);
}

.section-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin: var(--sp-6) 2px var(--sp-3);
}

.hero__num {
  font-size: 46px;
  font-weight: var(--fw-bold);
  font-variant-numeric: tabular-nums;
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 21px;
  padding: 0 8px;
  border-radius: var(--r-full);
  font-size: 0.6875em;
  font-weight: var(--fw-semibold);
  line-height: 1;
  transform: translateY(-3px);
}

/* 中文字形在行框内偏下，微微上提与左侧题号对齐 */
.chip__text {
  display: block;
  transform: translateY(-1px);
}

.chip::before {
  content: "";
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: currentColor;
}

.chip--bad {
  background: var(--danger-bg);
  color: var(--danger-text);
}

.chip--good {
  background: var(--success-bg);
  color: var(--success-text);
}

.qcard__type {
  margin-left: auto;
  font-size: 0.6875em;
  color: var(--text-disabled);
}

.stem {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-sm);
  line-height: 1.6;
  color: var(--text-primary);
}

.opts {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.opt {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  padding: 9px 10px;
  border-radius: var(--r-md);
  border: 1px solid transparent;
  background: var(--bg-muted);
  font-size: var(--fs-sm);
  line-height: 1.45;
  color: var(--text-secondary);
}

.opt__key {
  flex: 0 0 auto;
  width: 20px;
  text-align: center;
  font-size: var(--fs-xs);
  font-weight: var(--fw-bold);
  color: var(--text-secondary);
}

.opt__text {
  flex: 1;
  min-width: 0;
}

.opt__tag {
  flex: 0 0 auto;
  font-size: 0.6875em;
  font-weight: var(--fw-semibold);
  white-space: nowrap;
}

.opt.is-right {
  background: var(--success-bg);
  border-color: rgb(63 125 79 / 0.2);
  color: var(--success-text);
}

.opt.is-right .opt__key,
.opt.is-right .opt__tag {
  color: var(--success-text);
}

.opt.is-wrong {
  background: var(--danger-bg);
  border-color: rgb(217 58 74 / 0.2);
  color: var(--danger-text);
}

.opt.is-wrong .opt__key,
.opt.is-wrong .opt__tag {
  color: var(--danger-text);
}

.answer {
  margin: var(--sp-3) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.answer b {
  color: var(--text-primary);
  font-weight: var(--fw-semibold);
}

.note {
  display: flex;
  gap: var(--sp-2);
  margin-top: var(--sp-2);
  padding: 10px 12px;
  border-radius: var(--r-md);
  background: var(--brand-tint);
  font-size: var(--fs-xs);
  line-height: 1.7;
  color: var(--text-secondary);
}

.note__tag {
  flex: 0 0 auto;
  color: var(--brand-primary);
  font-weight: var(--fw-bold);
}

/* ── 入场动画 ────────────────────────────────────────── */
.rise {
  animation: rise 0.45s cubic-bezier(0.22, 0.8, 0.25, 1) both;
  animation-delay: var(--d, 0ms);
}

@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}

@keyframes grow {
  from {
    width: 0;
  }
  to {
    width: var(--p, 0%);
  }
}

@media (prefers-reduced-motion: reduce) {
  .rise,
  .bar__fill {
    animation: none !important;
  }

  .bar__fill {
    width: var(--p, 0%);
  }
}

@media (max-width: 340px) {
  .hero {
    margin: var(--sp-3);
    padding: var(--sp-5) var(--sp-3);
  }

  .hero__num {
    font-size: 2.25em;
  }

  .review {
    margin: 0 var(--sp-3);
  }
}

.hero__total {
  font-size: var(--fs-md);
  color: var(--text-secondary);
}

.hero__percent {
  margin: var(--sp-1) 0 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.hero__compare {
  display: inline-flex;
  align-items: center;
  gap: var(--sp-2);
  margin: var(--sp-4) 0 0;
  padding: 5px 12px;
  background: var(--bg-muted);
  border-radius: var(--r-full);
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.lottery-entry {
  margin: 0 var(--sp-4) var(--sp-4);
  text-align: center;
}

.lottery-entry__hint {
  margin: var(--sp-2) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.review {
  margin: 0 var(--sp-4);
}

.review__head {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
}

.rv {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: var(--sp-4);
  margin-bottom: var(--sp-3);
}

.rv__head {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  margin-bottom: var(--sp-3);
}

.rv__no {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--bg-muted);
  color: var(--text-secondary);
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  display: flex;
  align-items: center;
  justify-content: center;
}

.rv__type {
  margin-left: auto;
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

.rv__stem {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  line-height: 1.65;
}

.rv__answer {
  margin: var(--sp-2) 0 var(--sp-2);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--success-text);
}

.rv__opts {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
}

.rop {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  padding: var(--sp-2) var(--sp-3);
  border-radius: var(--r-md);
  background: var(--bg-page);
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.rop.is-right {
  background: var(--success-bg);
  color: var(--success-text);
}

.rop.is-wrong {
  background: var(--danger-bg);
  color: var(--danger-text);
}

.rop__letter {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--bg-surface);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.6875em;
  font-weight: var(--fw-semibold);
  flex-shrink: 0;
}

.rop__text {
  flex: 1;
}

.rop__tag {
  font-size: 0.6875em;
  font-weight: var(--fw-semibold);
}
</style>
