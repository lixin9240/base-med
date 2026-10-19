<script setup>
import { computed, ref } from 'vue'
import { adminRecords } from '@/mock/admin'
import { listRecords } from '@/store'
import { QUESTION_TYPE_LABEL, optionLetter } from '@/utils/question'

const PAGE_SIZE = 20

// 真实答题记录（用户端产生） + 演示数据，按完成时间倒序
const allRecords = [...listRecords(), ...adminRecords].sort(
  (a, b) => (b.completedAt ?? 0) - (a.completedAt ?? 0),
)

const filterVideo = ref('')
const filterResult = ref('')
const page = ref(1)
const activeRecord = ref(null)

const videoOptions = [...new Set(allRecords.map((r) => r.courseTitle).filter(Boolean))]

const filtered = computed(() =>
  allRecords.filter((r) => {
    if (filterVideo.value && r.courseTitle !== filterVideo.value) return false
    if (filterResult.value === 'pass' && !r.passed) return false
    if (filterResult.value === 'fail' && r.passed) return false
    return true
  }),
)

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)))

const paged = computed(() => {
  const start = (page.value - 1) * PAGE_SIZE
  return filtered.value.slice(start, start + PAGE_SIZE)
})

function onFilterChange() {
  page.value = 1
}

function fmtTime(ts) {
  if (!ts) return '—'
  const d = new Date(ts)
  const p = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}

function csvCell(value) {
  return `"${String(value ?? '').replace(/"/g, '""')}"`
}

function exportCsv() {
  const head = ['时间', '视频', '编号', '前测', '看课答题', '正确率', '结果']
  const lines = filtered.value.map((r) =>
    [
      fmtTime(r.completedAt),
      r.courseTitle,
      r.userId,
      `${r.preCorrect}/${r.preTotal}`,
      `${r.postCorrect}/${r.postTotal}`,
      `${r.percent}%`,
      r.passed ? '通过' : '未通过',
    ]
      .map(csvCell)
      .join(','),
  )
  const csv = '﻿' + [head.map(csvCell).join(','), ...lines].join('\r\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  const d = new Date()
  const p = (n) => String(n).padStart(2, '0')
  a.href = url
  a.download = `答题记录_${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

// ===== 查看作答 =====
const activePre = computed(() => {
  const r = activeRecord.value
  if (!r) return []
  const all = r.detail || []
  return r.postTotal ? all.slice(0, Math.max(0, all.length - r.postTotal)) : []
})

const activePost = computed(() => {
  const r = activeRecord.value
  if (!r) return []
  const all = r.detail || []
  return r.postTotal ? all.slice(all.length - r.postTotal) : all
})

function isRight(item, optIndex) {
  const letter = optionLetter(optIndex)
  if (item.type === 'multiple') return Array.isArray(item.answer) && item.answer.includes(letter)
  return item.answer === letter
}

function isPicked(item, optIndex) {
  const letter = optionLetter(optIndex)
  if (item.type === 'multiple')
    return Array.isArray(item.userAnswer) && item.userAnswer.includes(letter)
  return item.userAnswer === letter
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
        <select v-model="filterVideo" class="select" aria-label="按视频筛选" @change="onFilterChange">
          <option value="">全部视频</option>
          <option v-for="title in videoOptions" :key="title" :value="title">{{ title }}</option>
        </select>

        <select v-model="filterResult" class="select" aria-label="按结果筛选" @change="onFilterChange">
          <option value="">全部结果</option>
          <option value="pass">通过</option>
          <option value="fail">未通过</option>
        </select>

        <button type="button" class="btn btn--secondary" @click="exportCsv">
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
          导出
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

      <div v-for="record in paged" :key="record.id" class="records-row">
        <span class="col col--time">{{ fmtTime(record.completedAt) }}</span>
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
          <button type="button" class="link-btn" @click="activeRecord = record">查看作答</button>
        </span>
      </div>

      <div v-if="!paged.length" class="records-empty">暂无符合条件的答题记录</div>
    </div>

    <!-- 分页 -->
    <footer v-if="filtered.length" class="records-page__foot">
      <div class="pager">
        <button
          type="button"
          class="pager__btn"
          :disabled="page <= 1"
          aria-label="上一页"
          @click="page -= 1"
        >
          ‹
        </button>
        <button
          v-for="n in totalPages"
          :key="n"
          type="button"
          class="pager__btn"
          :class="{ 'pager__btn--active': n === page }"
          @click="page = n"
        >
          {{ n }}
        </button>
        <button
          type="button"
          class="pager__btn"
          :disabled="page >= totalPages"
          aria-label="下一页"
          @click="page += 1"
        >
          ›
        </button>
      </div>
      <span class="records-page__total">共 {{ filtered.length }} 条</span>
    </footer>

    <!-- 查看作答弹窗 -->
    <div v-if="activeRecord" class="modal" @click.self="activeRecord = null">
      <div class="modal__panel" role="dialog" aria-modal="true" aria-label="作答详情">
        <header class="modal__head">
          <div class="modal__intro">
            <h2 class="modal__title">作答详情</h2>
            <p class="modal__meta">
              {{ activeRecord.userId }} · 《{{ activeRecord.courseTitle }}》 ·
              {{ fmtTime(activeRecord.completedAt) }}
            </p>
          </div>
          <div class="modal__summary">
            <span v-if="activeRecord.preTotal" class="modal__score">
              前测 {{ activeRecord.preCorrect }}/{{ activeRecord.preTotal }}
            </span>
            <span class="modal__score">
              看课答题 {{ activeRecord.postCorrect }}/{{ activeRecord.postTotal }}
            </span>
            <span class="badge" :class="activeRecord.passed ? 'badge--success' : 'badge--warning'">
              <span class="badge__dot"></span>
              {{ activeRecord.passed ? '通过' : '未通过' }} · {{ activeRecord.percent }}%
            </span>
            <button type="button" class="modal__close" aria-label="关闭" @click="activeRecord = null">
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
          <section v-if="activePre.length" class="stage">
            <h3 class="stage__title">前测（看视频前）</h3>
            <article
              v-for="(item, index) in activePre"
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
              <ul class="rv__opts">
                <li
                  v-for="(opt, i) in item.options"
                  :key="i"
                  class="rop"
                  :class="{
                    'is-right': isRight(item, i),
                    'is-wrong': isPicked(item, i) && !isRight(item, i),
                  }"
                >
                  <span class="rop__letter">{{ optionLetter(i) }}</span>
                  <span class="rop__text">{{ opt }}</span>
                  <span v-if="isRight(item, i)" class="rop__tag">正确答案</span>
                  <span v-else-if="isPicked(item, i)" class="rop__tag rop__tag--wrong">学员选择</span>
                </li>
              </ul>
              <p v-if="item.analysis" class="rv__analysis"><b>解析：</b>{{ item.analysis }}</p>
            </article>
          </section>

          <section class="stage">
            <h3 class="stage__title">看课答题（看完视频后）</h3>
            <article
              v-for="(item, index) in activePost"
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
              <ul class="rv__opts">
                <li
                  v-for="(opt, i) in item.options"
                  :key="i"
                  class="rop"
                  :class="{
                    'is-right': isRight(item, i),
                    'is-wrong': isPicked(item, i) && !isRight(item, i),
                  }"
                >
                  <span class="rop__letter">{{ optionLetter(i) }}</span>
                  <span class="rop__text">{{ opt }}</span>
                  <span v-if="isRight(item, i)" class="rop__tag">正确答案</span>
                  <span v-else-if="isPicked(item, i)" class="rop__tag rop__tag--wrong">学员选择</span>
                </li>
              </ul>
              <p v-if="item.analysis" class="rv__analysis"><b>解析：</b>{{ item.analysis }}</p>
            </article>
          </section>
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
  background: var(--bg-surface-solid);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: var(--fw-semibold);
  flex-shrink: 0;
}

.rop__text {
  flex: 1;
}

.rop__tag {
  font-size: 11px;
  font-weight: var(--fw-semibold);
}

.rv__analysis {
  margin: var(--sp-3) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
  line-height: 1.7;
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
