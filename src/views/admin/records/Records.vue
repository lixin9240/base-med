<script setup>
import { computed, onMounted, ref } from 'vue'
import { exportAdminRecords, getAdminRecordDetail, listAdminRecords, listAdminVideos } from '@/api'
import { QUESTION_TYPE_LABEL } from '@/utils/question'

const PAGE_SIZE = 20

/** 2.5.1 接口返回的记录（字段已转成列表展示用） */
const rows = ref([])
const total = ref(0)
const page = ref(1)
const loading = ref(false)
const loadError = ref('')
const exporting = ref(false)

const filterVideo = ref('')
const filterResult = ref('')
const detailOpen = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const detail = ref(null)

/** 「全部视频」下拉选项：复用已接入的视频列表接口（文档 2.5.1 注明选项来自视频列表） */
const videoOptions = ref([])

// ===== 视频筛选：可输入搜索的组合框 =====
const videoPickerOpen = ref(false)
const videoSearch = ref('')

/** 当前选中的视频标题，输入框失焦/关闭下拉时回填 */
const selectedVideoTitle = computed(() => {
  const found = videoOptions.value.find((item) => String(item.id) === String(filterVideo.value))
  return found?.title || ''
})

/** 按输入关键字过滤视频选项（不区分大小写） */
const filteredVideoOptions = computed(() => {
  const keyword = videoSearch.value.trim().toLowerCase()
  if (!keyword) return videoOptions.value
  return videoOptions.value.filter((item) => item.title.toLowerCase().includes(keyword))
})

function openVideoPicker() {
  videoPickerOpen.value = true
}

function onVideoFocus(event) {
  videoPickerOpen.value = true
  // 已选视频时全选标题，方便直接输入覆盖搜索
  event.target.select?.()
}

function closeVideoPicker() {
  videoPickerOpen.value = false
  videoSearch.value = selectedVideoTitle.value
}

function selectVideo(item) {
  filterVideo.value = item?.id ?? ''
  videoSearch.value = item?.title ?? ''
  videoPickerOpen.value = false
  onFilterChange()
}

function clearVideoFilter() {
  selectVideo(null)
}

/** 回车直接选中第一条匹配项 */
function onVideoEnter() {
  if (!videoPickerOpen.value) return
  const first = filteredVideoOptions.value[0]
  if (first) selectVideo(first)
}

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / PAGE_SIZE)))

/** 把 2.5.1 接口返回的一条记录转成列表页展示需要的字段 */
function toRow(item) {
  return {
    id: item.id,
    time: item.submit_at || '—',
    courseTitle: item.video_title || '',
    userId: item.dr_id || '',
    preCorrect: Number(item.pre_correct) || 0,
    preTotal: Number(item.pre_total) || 0,
    postCorrect: Number(item.post_correct) || 0,
    postTotal: Number(item.post_total) || 0,
    percent: Number(item.accuracy_rate) || 0,
    passed: item.result_status === 'pass',
  }
}

async function refreshRecords() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await listAdminRecords({
      page: page.value,
      pageSize: PAGE_SIZE,
      videoId: filterVideo.value,
      resultStatus: filterResult.value,
    })
    rows.value = data.list.map(toRow)
    total.value = data.total
  } catch (error) {
    rows.value = []
    total.value = 0
    loadError.value = error?.message || '答题记录加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

async function loadVideoOptions() {
  try {
    const data = await listAdminVideos({ page: 1, pageSize: 100 })
    videoOptions.value = data.list.map((item) => ({ id: item.id, title: item.title }))
  } catch {
    // 下拉仅用于筛选，视频列表取不到时不影响答题记录列表本身
    videoOptions.value = []
  }
}

onMounted(() => {
  refreshRecords()
  loadVideoOptions()
})

function onFilterChange() {
  page.value = 1
  refreshRecords()
}

function goPage(next) {
  page.value = next
  refreshRecords()
}

/** 导出（2.5.3）：带上当前筛选条件，后端生成 xlsx 并返回签名下载地址，前端直接打开 */
async function exportRecords() {
  if (exporting.value) return

  exporting.value = true
  loadError.value = ''
  try {
    const data = await exportAdminRecords({
      videoId: filterVideo.value,
      resultStatus: filterResult.value,
    })
    window.open(data.url, '_blank', 'noopener')
  } catch (error) {
    loadError.value = error?.message || '导出失败，请稍后重试'
  } finally {
    exporting.value = false
  }
}

// ===== 查看作答（2.5.2）=====
/** 多选的 "A,B,C" 渲染为 "A、B、C"（可自定义分隔符）；未作答返回空串 */
function formatAnswer(value, separator = '、') {
  if (value === null || value === undefined || value === '') return ''
  return String(value)
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean)
    .join(separator)
}

function toAnswerItem(item) {
  return {
    questionId: item.question_id,
    type: item.type,
    stem: item.stem || '',
    correct: Number(item.is_correct) === 1,
    correctAnswer: formatAnswer(item.correct_answer),
    userAnswer: formatAnswer(item.user_answer, ''), // 用户选择去掉顿号
  }
}

async function openDetail(row) {
  detailOpen.value = true
  detailLoading.value = true
  detailError.value = ''
  detail.value = null
  try {
    const data = await getAdminRecordDetail(row.id)
    const list = Array.isArray(data.list) ? data.list : []
    detail.value = {
      videoTitle: data.video_title || '',
      drId: data.dr_id || '',
      submitAt: data.submit_at || '—',
      passed: data.result_status === 'pass',
      percent: Number(data.accuracy_rate) || 0,
      pre: {
        correct: Number(data.pre?.correct) || 0,
        total: Number(data.pre?.total) || 0,
      },
      post: {
        correct: Number(data.post?.correct) || 0,
        total: Number(data.post?.total) || 0,
      },
      preItems: list.filter((item) => item.stage === 'pretest').map(toAnswerItem),
      postItems: list.filter((item) => item.stage === 'posttest').map(toAnswerItem),
    }
  } catch (error) {
    detailError.value = error?.message || '作答详情加载失败，请稍后重试'
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  detailOpen.value = false
  detail.value = null
  detailError.value = ''
}
</script>

<template>
  <section class="records-page">
    <!-- 页头 -->
    <header class="records-page__head">
      <div class="records-page__intro">
        <h1 class="records-page__title">答题记录</h1>
        <p class="records-page__desc">
          用户端免登录，每条记录只有一个随机编号，不涉及任何个人信息。
        </p>
      </div>

      <div class="records-page__actions">
        <div class="vselect">
          <div class="vselect__control" :class="{ 'is-open': videoPickerOpen }">
            <svg
              class="vselect__icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <circle cx="11" cy="11" r="6.5" />
              <path d="m20 20-3.5-3.5" />
            </svg>
            <input
              v-model="videoSearch"
              class="vselect__input"
              type="text"
              role="combobox"
              aria-label="按视频筛选"
              aria-controls="video-filter-options"
              :aria-expanded="videoPickerOpen"
              :placeholder="filterVideo ? '' : '全部视频'"
              autocomplete="off"
              @focus="onVideoFocus"
              @input="openVideoPicker"
              @keydown.enter.prevent="onVideoEnter"
              @keydown.esc="closeVideoPicker"
              @blur="closeVideoPicker"
            />
            <button
              v-if="filterVideo"
              type="button"
              class="vselect__clear"
              aria-label="清除视频筛选"
              @mousedown.prevent
              @click="clearVideoFilter"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          </div>

          <ul v-if="videoPickerOpen" id="video-filter-options" class="vselect__menu" role="listbox">
            <li>
              <button
                type="button"
                class="vselect__option"
                :class="{ 'is-active': !filterVideo }"
                role="option"
                :aria-selected="!filterVideo"
                @mousedown.prevent
                @click="selectVideo(null)"
              >
                全部视频
              </button>
            </li>
            <li v-for="item in filteredVideoOptions" :key="item.id">
              <button
                type="button"
                class="vselect__option"
                :class="{ 'is-active': String(filterVideo) === String(item.id) }"
                role="option"
                :aria-selected="String(filterVideo) === String(item.id)"
                @mousedown.prevent
                @click="selectVideo(item)"
              >
                {{ item.title }}
              </button>
            </li>
            <li v-if="!filteredVideoOptions.length" class="vselect__empty">没有匹配的视频</li>
          </ul>
        </div>

        <select v-model="filterResult" class="select" aria-label="按结果筛选" @change="onFilterChange">
          <option value="">全部结果</option>
          <option value="pass">通过</option>
          <option value="fail">未通过</option>
        </select>

        <button
          type="button"
          class="btn btn--secondary"
          :disabled="exporting"
          @click="exportRecords"
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
          {{ exporting ? '导出中…' : '导出' }}
        </button>
      </div>
    </header>

    <!-- 记录列表 -->
    <div class="records-table">
      <div class="records-table__head">
        <span class="col col--time">时间</span>
        <span class="col col--video">视频</span>
        <span class="col col--uid">编号</span>
        <span class="col col--score">前测</span>
        <span class="col col--score">看课答题</span>
        <span class="col col--percent">正确率</span>
        <span class="col col--result">结果</span>
        <span class="col col--ops">操作</span>
      </div>

      <div v-for="record in rows" :key="record.id" class="records-row">
        <span class="col col--time">{{ record.time }}</span>
        <span class="col col--video">{{ record.courseTitle }}</span>
        <span class="col col--uid">{{ record.userId }}</span>
        <span class="col col--score">{{ record.preCorrect }}/{{ record.preTotal }}</span>
        <span class="col col--score">{{ record.postCorrect }}/{{ record.postTotal }}</span>
        <span class="col col--percent" :class="record.passed ? 'percent--pass' : 'percent--fail'">
          {{ record.percent }}%
        </span>
        <span class="col col--result">
          <span class="badge" :class="record.passed ? 'badge--success' : 'badge--warning'">
            <span class="badge__dot"></span>
            {{ record.passed ? '通过' : '未通过' }}
          </span>
        </span>
        <span class="col col--ops">
          <button type="button" class="link-btn" @click="openDetail(record)">查看作答</button>
        </span>
      </div>

      <div v-if="loadError" class="records-empty">{{ loadError }}</div>
      <div v-else-if="!rows.length" class="records-empty">
        {{ loading ? '加载中…' : '暂无符合条件的答题记录' }}
      </div>
    </div>

    <!-- 分页 -->
    <footer v-if="total" class="records-page__foot">
      <div class="pager">
        <button
          type="button"
          class="pager__btn"
          :disabled="page <= 1 || loading"
          aria-label="上一页"
          @click="goPage(page - 1)"
        >
          ‹
        </button>
        <button
          v-for="n in totalPages"
          :key="n"
          type="button"
          class="pager__btn"
          :class="{ 'pager__btn--active': n === page }"
          :disabled="loading"
          @click="goPage(n)"
        >
          {{ n }}
        </button>
        <button
          type="button"
          class="pager__btn"
          :disabled="page >= totalPages || loading"
          aria-label="下一页"
          @click="goPage(page + 1)"
        >
          ›
        </button>
      </div>
      <span class="records-page__total">共 {{ total }} 条</span>
    </footer>

    <!-- 查看作答弹窗（2.5.2） -->
    <div v-if="detailOpen" class="modal" @click.self="closeDetail">
      <div class="modal__panel" role="dialog" aria-modal="true" aria-label="作答详情">
        <header class="modal__head">
          <div class="modal__intro">
            <h2 class="modal__title">作答详情</h2>
            <p class="modal__meta">
              <template v-if="detail">
                {{ detail.drId }} · 《{{ detail.videoTitle }}》 · {{ detail.submitAt }}
              </template>
              <template v-else>正在加载作答详情…</template>
            </p>
          </div>
          <div class="modal__summary">
            <template v-if="detail">
              <span v-if="detail.pre.total" class="modal__score">
                前测 {{ detail.pre.correct }}/{{ detail.pre.total }}
              </span>
              <span class="modal__score">
                看课答题 {{ detail.post.correct }}/{{ detail.post.total }}
              </span>
              <span class="badge" :class="detail.passed ? 'badge--success' : 'badge--warning'">
                <span class="badge__dot"></span>
                {{ detail.passed ? '通过' : '未通过' }} · {{ detail.percent }}%
              </span>
            </template>
            <button type="button" class="modal__close" aria-label="关闭" @click="closeDetail">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                aria-hidden="true"
              >
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          </div>
        </header>

        <div class="modal__body">
          <p v-if="detailError" class="records-empty">{{ detailError }}</p>
          <p v-else-if="detailLoading" class="records-empty">加载中…</p>
          <template v-else-if="detail">
            <section v-if="detail.preItems.length" class="stage">
              <h3 class="stage__title">前测（看视频前）</h3>
              <article
                v-for="(item, index) in detail.preItems"
                :key="item.questionId || index"
                class="rv"
                :class="item.correct ? 'rv--ok' : 'rv--bad'"
              >
                <div class="rv__head">
                  <span class="rv__no">{{ index + 1 }}</span>
                  <span class="badge" :class="item.correct ? 'badge--success' : 'badge--danger'">
                    <span class="badge__dot"></span>
                    {{ item.correct ? '答对' : '答错' }}
                  </span>
                  <span class="rv__type">{{ QUESTION_TYPE_LABEL[item.type] }}</span>
                </div>
                <p class="rv__stem">{{ item.stem }}</p>
                <div class="rv__answers">
                  <span class="rv__answer">
                    正确答案：<b>{{ item.correctAnswer || '—' }}</b>
                  </span>
                  <span v-if="!item.correct" class="rv__answer rv__answer--wrong">
                    用户选择：<b>{{ item.userAnswer || '未作答' }}</b>
                  </span>
                </div>
              </article>
            </section>

            <section v-if="detail.postItems.length" class="stage">
              <h3 class="stage__title">看课答题（看完视频后）</h3>
              <article
                v-for="(item, index) in detail.postItems"
                :key="item.questionId || index"
                class="rv"
                :class="item.correct ? 'rv--ok' : 'rv--bad'"
              >
                <div class="rv__head">
                  <span class="rv__no">{{ index + 1 }}</span>
                  <span class="badge" :class="item.correct ? 'badge--success' : 'badge--danger'">
                    <span class="badge__dot"></span>
                    {{ item.correct ? '答对' : '答错' }}
                  </span>
                  <span class="rv__type">{{ QUESTION_TYPE_LABEL[item.type] }}</span>
                </div>
                <p class="rv__stem">{{ item.stem }}</p>
                <div class="rv__answers">
                  <span class="rv__answer">
                    正确答案：<b>{{ item.correctAnswer || '—' }}</b>
                  </span>
                  <span v-if="!item.correct" class="rv__answer rv__answer--wrong">
                    用户选择：<b>{{ item.userAnswer || '未作答' }}</b>
                  </span>
                </div>
              </article>
            </section>

            <p v-if="!detail.preItems.length && !detail.postItems.length" class="records-empty">
              这条记录暂无逐题作答明细
            </p>
          </template>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.records-page {
  display: flex;
  flex-direction: column;
  gap: var(--sp-5);
}

/* ===== 页头 ===== */
.records-page__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--sp-4);
  flex-wrap: wrap;
}

.records-page__intro {
  display: flex;
  flex-direction: column;
  gap: var(--sp-1);
}

.records-page__title {
  margin: 0;
  font-size: var(--fs-2xl);
  font-weight: var(--fw-bold);
  color: var(--text-primary);
}

.records-page__desc {
  margin: 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.records-page__actions {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
}

/* ===== 筛选与按钮 ===== */
.select {
  height: 40px;
  padding: 0 var(--sp-4);
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-sm);
  cursor: pointer;
}

/* ===== 视频筛选（可输入搜索） ===== */
.vselect {
  position: relative;
  width: 260px;
  max-width: 100%;
}

.vselect__control {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  height: 40px;
  padding: 0 var(--sp-3);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-sm);
  transition: border-color var(--t-fast);
}

.vselect__control:focus-within,
.vselect__control.is-open {
  border-color: var(--brand-primary-hover);
}

.vselect__icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: var(--text-secondary);
}

.vselect__input {
  flex: 1;
  min-width: 0;
  padding: 0;
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: none;
  border: none;
  outline: none;
}

.vselect__clear {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  padding: 0;
  flex-shrink: 0;
  color: var(--text-secondary);
  background: var(--bg-muted);
  border: none;
  border-radius: 50%;
  cursor: pointer;
}

.vselect__clear:hover {
  color: var(--text-primary);
  background: var(--border);
}

.vselect__clear svg {
  width: 10px;
  height: 10px;
}

.vselect__menu {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 30;
  max-height: 280px;
  margin: 0;
  padding: var(--sp-1);
  overflow-y: auto;
  list-style: none;
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  box-shadow: var(--shadow-lg);
}

.vselect__option {
  display: block;
  width: 100%;
  padding: 8px 12px;
  font-size: var(--fs-sm);
  color: var(--text-primary);
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  background: none;
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: background var(--t-fast);
}

.vselect__option:hover,
.vselect__option.is-active {
  color: var(--brand-primary-hover);
  background: var(--brand-tint);
}

.vselect__empty {
  padding: var(--sp-3);
  font-size: var(--fs-xs);
  color: var(--text-disabled);
  text-align: center;
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

/* ===== 列表 ===== */
.records-table {
  display: flex;
  flex-direction: column;
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.records-table__head,
.records-row {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
  padding: var(--sp-3) var(--sp-5);
}

.records-table__head {
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-bottom: 1px solid var(--border);
}

.records-row {
  font-size: var(--fs-sm);
  border-bottom: 1px solid var(--border-light);
  transition: background var(--t-fast);
}

.records-row:last-of-type {
  border-bottom: none;
}

.records-row:hover {
  background: var(--brand-tint);
}

.col {
  display: flex;
  align-items: center;
  min-width: 0;
}

.col--time {
  flex: 1.1;
  color: var(--text-secondary);
  white-space: nowrap;
}

.col--video {
  flex: 1.6;
  font-weight: var(--fw-medium);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.col--uid {
  flex: 1.1;
  color: var(--text-secondary);
  white-space: nowrap;
}

.col--score {
  flex: 0.7;
  justify-content: center;
}

.col--percent {
  flex: 0.6;
  justify-content: center;
  font-weight: var(--fw-semibold);
}

.percent--pass {
  color: var(--success);
}

.percent--fail {
  color: var(--danger);
}

.col--result {
  flex: 0.7;
  justify-content: center;
}

.col--ops {
  flex: 0.7;
  justify-content: flex-end;
}

.records-empty {
  padding: var(--sp-10);
  text-align: center;
  font-size: var(--fs-sm);
  color: var(--text-disabled);
}

/* ===== 标签 ===== */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  border-radius: var(--r-full);
  white-space: nowrap;
}

.badge__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.badge--success {
  color: var(--success-text);
  background: var(--success-bg);
}

.badge--warning {
  color: var(--warning-text);
  background: var(--warning-bg);
}

.badge--danger {
  color: var(--danger-text);
  background: var(--danger-bg);
}

/* ===== 操作链接 ===== */
.link-btn {
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  background: none;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  transition: color var(--t-fast);
}

.link-btn:hover {
  color: var(--brand-primary-active);
  text-decoration: underline;
}

/* ===== 分页 ===== */
.records-page__foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-4);
}

.pager {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
}

.pager__btn {
  min-width: 32px;
  height: 32px;
  padding: 0 var(--sp-2);
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.pager__btn:hover:not(:disabled) {
  background: var(--bg-muted);
}

.pager__btn:disabled {
  color: var(--text-disabled);
  cursor: not-allowed;
}

.pager__btn--active {
  color: #fff;
  background: var(--brand-primary-hover);
  border-color: var(--brand-primary-hover);
}

.records-page__total {
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

/* ===== 弹窗 ===== */
.modal {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-6);
  background: rgb(15 31 23 / 0.45);
}

.modal__panel {
  display: flex;
  flex-direction: column;
  width: min(760px, 100%);
  max-height: 85vh;
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.modal__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--sp-4);
  padding: var(--sp-5) var(--sp-6);
  border-bottom: 1px solid var(--border);
}

.modal__title {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: var(--fw-bold);
}

.modal__meta {
  margin: var(--sp-1) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.modal__summary {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  flex-shrink: 0;
}

.modal__score {
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  color: var(--text-secondary);
}

.modal__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  color: var(--text-secondary);
  background: var(--bg-muted);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: background var(--t-fast);
}

.modal__close:hover {
  background: var(--brand-soft);
}

.modal__close svg {
  width: 16px;
  height: 16px;
}

.modal__body {
  flex: 1;
  padding: var(--sp-5) var(--sp-6);
  overflow-y: auto;
}

.stage + .stage {
  margin-top: var(--sp-6);
}

.stage__title {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

/* ===== 逐题卡片 ===== */
.rv {
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-left-width: 3px;
  border-radius: var(--r-lg);
  padding: var(--sp-4);
  margin-bottom: var(--sp-3);
}

.rv--ok {
  border-left-color: var(--success);
}

.rv--bad {
  border-left-color: var(--danger);
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

/* ===== 答案区（2.5.2 接口只给正确答案/用户选择，不给选项列表）===== */
.rv__answers {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
}

.rv__answer {
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  background: var(--bg-page);
  border-radius: var(--r-md);
}

.rv__answer--wrong {
  color: var(--danger-text);
  background: var(--danger-bg);
}

/* ===== 窄屏横向滚动 ===== */
@media (max-width: 1100px) {
  .records-table {
    overflow-x: auto;
  }

  .records-table__head,
  .records-row {
    min-width: 960px;
  }
}
</style>
