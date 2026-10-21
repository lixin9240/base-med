<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  exportAdminStatistics,
  getAdminStatisticsOverview,
  listAdminStatisticsVideos,
  listAdminStatisticsWrongQuestions,
} from '@/api'
import { PASS_PERCENT } from '@/store'

const overview = ref(null)
const videoStats = ref([])
const errorQuestions = ref([])
const loading = ref(false)
const loadError = ref('')
const exporting = ref(false)

// ===== 顶部统计卡（2.6.1）=====
const videoTotal = computed(() => Number(overview.value?.video_total) || 0)
const publishedCount = computed(() => Number(overview.value?.video_online) || 0)
const totalAttempts = computed(() => Number(overview.value?.answer_total_times) || 0)
const avgPercent = computed(() => Number(overview.value?.avg_accuracy) || 0)
const passRate = computed(() => Number(overview.value?.pass_rate) || 0)
/** 通过线取自 system_config，接口未返回时回退本地默认值 */
const passScore = computed(() => Number(overview.value?.pass_score) || PASS_PERCENT)

function barTone(rate) {
  if (rate >= 80) return 'success'
  if (rate >= PASS_PERCENT) return 'warning'
  return 'danger'
}

/** 把 2.6.2 接口返回的一条记录转成表格展示字段 */
function toVideoRow(item) {
  return {
    id: item.video_id,
    title: item.title || '',
    category: item.category_name || '',
    published: Number(item.status) === 1,
    attempts: Number(item.answer_times) || 0,
    avgPercent:
      item.avg_accuracy === null || item.avg_accuracy === undefined
        ? null
        : Number(item.avg_accuracy),
    passRate:
      item.pass_rate === null || item.pass_rate === undefined ? null : Number(item.pass_rate),
  }
}

/** 把 2.6.3 接口返回的一条记录转成表格展示字段 */
function toQuestionRow(item) {
  return {
    key: item.question_id ?? item.stem,
    stem: item.stem || '',
    courseTitle: item.video_title || '',
    stage: item.stage === 'posttest' ? '后测' : '前测',
    attempts: Number(item.answer_times) || 0,
    errorRate: Number(item.wrong_rate) || 0,
  }
}

async function refreshStatistics() {
  loading.value = true
  loadError.value = ''
  try {
    const [overviewData, videos, questions] = await Promise.all([
      getAdminStatisticsOverview(),
      listAdminStatisticsVideos(),
      listAdminStatisticsWrongQuestions(),
    ])
    overview.value = overviewData
    videoStats.value = videos.map(toVideoRow)
    errorQuestions.value = questions.map(toQuestionRow)
  } catch (error) {
    overview.value = null
    videoStats.value = []
    errorQuestions.value = []
    loadError.value = error?.message || '数据统计加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

onMounted(refreshStatistics)

// ===== 导出统计（2.6.4）=====
async function exportStatistics() {
  if (exporting.value) return

  exporting.value = true
  loadError.value = ''
  try {
    const blob = await exportAdminStatistics()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    const d = new Date()
    const p = (n) => String(n).padStart(2, '0')
    a.href = url
    a.download = `数据统计_${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}.xlsx`
    a.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    loadError.value = error?.message || '导出失败，请稍后重试'
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <section class="stats-page">
    <!-- 页头 -->
    <header class="stats-page__head">
      <div class="stats-page__intro">
        <h1 class="stats-page__title">数据统计</h1>
        <p class="stats-page__desc">只看三件事：有多少人学了、答得怎么样、哪道题最容易错。</p>
      </div>

      <button
        type="button"
        class="btn btn--secondary"
        :disabled="exporting"
        @click="exportStatistics"
      >
        <svg
          class="btn__icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M12 4v11m0 0 4.5-4.5M12 15l-4.5-4.5" />
          <path d="M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17" />
        </svg>
        {{ exporting ? '导出中…' : '导出统计' }}
      </button>
    </header>

    <p v-if="loadError" class="stats-banner">{{ loadError }}</p>

    <!-- 统计卡 -->
    <div class="stat-cards">
      <div class="stat-card">
        <div class="stat-card__label">
          视频
          <span class="stat-card__icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <rect x="3" y="7" width="12" height="10" rx="2.5" />
              <path d="m15 10.5 6-3.2v9.4l-6-3.2" />
            </svg>
          </span>
        </div>
        <p class="stat-card__num">
          {{ videoTotal }}<span class="stat-card__unit">条</span>
        </p>
        <p class="stat-card__sub">其中已上架 {{ publishedCount }} 条</p>
      </div>

      <div class="stat-card">
        <div class="stat-card__label">
          答题次数
          <span class="stat-card__icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
              <circle cx="9.5" cy="7.5" r="3.5" />
              <path d="M21 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M15.5 4.13a3.5 3.5 0 0 1 0 6.74" />
            </svg>
          </span>
        </div>
        <p class="stat-card__num">
          {{ totalAttempts }}<span class="stat-card__unit">次</span>
        </p>
        <p class="stat-card__sub">用户提交的答题合计</p>
      </div>

      <div class="stat-card">
        <div class="stat-card__label">
          平均正确率
          <span class="stat-card__icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              aria-hidden="true"
            >
              <circle cx="12" cy="12" r="9" />
              <circle cx="12" cy="12" r="5" />
              <circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none" />
            </svg>
          </span>
        </div>
        <p class="stat-card__num">
          {{ avgPercent }}<span class="stat-card__unit">%</span>
        </p>
        <p class="stat-card__sub">所有答题记录的平均值</p>
      </div>

      <div class="stat-card">
        <div class="stat-card__label">
          通过率
          <span class="stat-card__icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <circle cx="12" cy="12" r="9" />
              <path d="m8.5 12.2 2.4 2.4 4.6-4.9" />
            </svg>
          </span>
        </div>
        <p class="stat-card__num">
          {{ passRate }}<span class="stat-card__unit">%</span>
        </p>
        <p class="stat-card__sub">正确率达到 {{ passScore }}% 视为通过</p>
      </div>
    </div>

    <!-- 各视频答题情况 -->
    <div class="panel">
      <h2 class="panel__title">
        <svg
          class="panel__icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <rect x="3" y="7" width="12" height="10" rx="2.5" />
          <path d="m15 10.5 6-3.2v9.4l-6-3.2" />
        </svg>
        各视频答题情况
      </h2>

      <div class="vtable">
        <div class="vtable__head">
          <span class="col col--video">视频</span>
          <span class="col col--status">状态</span>
          <span class="col col--num">答题次数</span>
          <span class="col col--num">平均正确率</span>
          <span class="col col--rate">通过率</span>
        </div>

        <div v-for="video in videoStats" :key="video.id" class="vtable__row">
          <div class="col col--video">
            <div class="vcell">
              <span class="vcell__title">{{ video.title }}</span>
              <span class="vcell__category">{{ video.category }}</span>
            </div>
          </div>
          <span class="col col--status">
            <span class="status" :class="{ 'status--off': !video.published }">
              <span class="status__dot"></span>
              {{ video.published ? '已上架' : '已下架' }}
            </span>
          </span>
          <span class="col col--num">{{ video.attempts }}</span>
          <span class="col col--num">
            {{ video.avgPercent === null ? '—' : `${video.avgPercent}%` }}
          </span>
          <span class="col col--rate">
            <template v-if="video.passRate !== null">
              <span class="rate__num">{{ video.passRate }}%</span>
              <span class="rate__track">
                <span
                  class="rate__bar"
                  :class="`rate__bar--${barTone(video.passRate)}`"
                  :style="{ width: `${video.passRate}%` }"
                ></span>
              </span>
            </template>
            <template v-else>—</template>
          </span>
        </div>
      </div>
    </div>

    <!-- 最容易答错的题 -->
    <div class="panel">
      <div class="panel__head">
        <h2 class="panel__title">
          <svg
            class="panel__icon panel__icon--warn"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M12 4 2.8 19.5h18.4L12 4Z" />
            <path d="M12 10v4" />
            <path d="M12 17.2v.3" />
          </svg>
          最容易答错的题
        </h2>
        <p class="panel__note">按错误率从高到低，可以用来调整讲解重点</p>
      </div>

      <div class="qtable">
        <div class="qtable__head">
          <span class="col col--stem">题干</span>
          <span class="col col--course">所属视频</span>
          <span class="col col--stage">环节</span>
          <span class="col col--attempts">作答次数</span>
          <span class="col col--error">错误率</span>
        </div>

        <div v-for="q in errorQuestions" :key="q.key" class="qtable__row">
          <span class="col col--stem">{{ q.stem }}</span>
          <span class="col col--course">{{ q.courseTitle }}</span>
          <span class="col col--stage">{{ q.stage }}</span>
          <span class="col col--attempts">{{ q.attempts }}</span>
          <span class="col col--error">{{ q.errorRate }}%</span>
        </div>

        <div v-if="!errorQuestions.length" class="qtable__empty">还没有足够的作答数据</div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.stats-page {
  display: flex;
  flex-direction: column;
  gap: var(--sp-5);
}

.stats-banner {
  margin: 0;
  padding: var(--sp-3) var(--sp-4);
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-md);
}

/* ===== 页头 ===== */
.stats-page__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--sp-4);
  flex-wrap: wrap;
}

.stats-page__intro {
  display: flex;
  flex-direction: column;
  gap: var(--sp-1);
}

.stats-page__title {
  margin: 0;
  font-size: var(--fs-2xl);
  font-weight: var(--fw-bold);
  color: var(--text-primary);
}

.stats-page__desc {
  margin: 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  height: 40px;
  padding: 0 var(--sp-5);
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  border: 1px solid transparent;
  border-radius: var(--r-md);
  cursor: pointer;
  transition: background var(--t-fast);
}

.btn__icon {
  width: 16px;
  height: 16px;
}

.btn--secondary {
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border-color: var(--border);
  box-shadow: var(--shadow-sm);
}

.btn--secondary:hover {
  background: var(--bg-muted);
}

/* ===== 统计卡 ===== */
.stat-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--sp-4);
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
  padding: var(--sp-5);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
}

.stat-card__label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.stat-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  color: var(--brand-primary-hover);
  background: var(--brand-tint);
  border-radius: var(--r-md);
}

.stat-card__icon svg {
  width: 17px;
  height: 17px;
}

.stat-card__num {
  margin: 0;
  font-size: 30px;
  font-weight: var(--fw-bold);
  line-height: 1.1;
  color: var(--text-primary);
}

.stat-card__unit {
  margin-left: var(--sp-1);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
}

.stat-card__sub {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

/* ===== 面板 ===== */
.panel {
  display: flex;
  flex-direction: column;
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.panel__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--sp-4);
  padding: var(--sp-4) var(--sp-5) 0;
}

.panel__title {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  margin: 0;
  padding: var(--sp-4) var(--sp-5) 0;
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.panel__head .panel__title {
  padding: 0;
}

.panel__icon {
  width: 17px;
  height: 17px;
  color: var(--brand-primary-hover);
}

.panel__icon--warn {
  color: var(--warning-text);
}

.panel__note {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

/* ===== 表格通用 ===== */
.vtable,
.qtable {
  display: flex;
  flex-direction: column;
  margin-top: var(--sp-4);
  border-top: 1px solid var(--border);
}

.vtable__head,
.vtable__row,
.qtable__head,
.qtable__row {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
  padding: var(--sp-3) var(--sp-5);
}

.vtable__head,
.qtable__head {
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-bottom: 1px solid var(--border);
}

.vtable__row,
.qtable__row {
  font-size: var(--fs-sm);
  border-bottom: 1px solid var(--border-light);
  transition: background var(--t-fast);
}

.vtable__row:last-child,
.qtable__row:last-child {
  border-bottom: none;
}

.vtable__row:hover,
.qtable__row:hover {
  background: var(--brand-tint);
}

.col {
  display: flex;
  align-items: center;
  min-width: 0;
}

/* ===== 各视频答题情况 ===== */
.col--video {
  flex: 2;
}

.vcell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.vcell__title {
  font-weight: var(--fw-semibold);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.vcell__category {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.col--status {
  flex: 1.1;
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--success-text);
  background: var(--success-bg);
  border-radius: var(--r-full);
  white-space: nowrap;
}

.status--off {
  color: var(--text-secondary);
  background: #eef2f0;
}

.status__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.col--num {
  flex: 0.9;
  justify-content: center;
}

.col--rate {
  flex: 1.4;
  justify-content: flex-end;
  gap: var(--sp-3);
}

.rate__num {
  min-width: 42px;
  text-align: right;
  font-weight: var(--fw-semibold);
}

.rate__track {
  flex: 1;
  max-width: 140px;
  height: 8px;
  background: var(--bg-muted);
  border-radius: var(--r-full);
  overflow: hidden;
}

.rate__bar {
  display: block;
  height: 100%;
  border-radius: var(--r-full);
}

.rate__bar--success {
  background: var(--success);
}

.rate__bar--warning {
  background: #e8a13c;
}

.rate__bar--danger {
  background: var(--danger);
}

/* ===== 最容易答错的题 ===== */
.col--stem {
  flex: 2.6;
  line-height: 1.5;
}

.col--course {
  flex: 1.1;
  color: var(--text-secondary);
}

.col--stage {
  flex: 0.5;
  justify-content: center;
  color: var(--text-secondary);
}

.col--attempts {
  flex: 0.6;
  justify-content: center;
}

.col--error {
  flex: 0.5;
  justify-content: flex-end;
  font-weight: var(--fw-semibold);
  color: var(--danger);
}

.qtable__empty {
  padding: var(--sp-10);
  text-align: center;
  font-size: var(--fs-sm);
  color: var(--text-disabled);
}

/* ===== 窄屏 ===== */
@media (max-width: 1100px) {
  .stat-cards {
    grid-template-columns: repeat(2, 1fr);
  }

  .vtable,
  .qtable {
    overflow-x: auto;
  }

  .vtable__head,
  .vtable__row {
    min-width: 860px;
  }

  .qtable__head,
  .qtable__row {
    min-width: 900px;
  }
}
</style>
