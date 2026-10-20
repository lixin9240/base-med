<script setup>
import { computed, ref, watch } from 'vue'
import { QUESTION_TYPE_LABEL } from '@/utils/question'
import { TEMPLATE_PATH, parseQuestionFile } from '@/utils/video/questionImport'
import { importVideoQuestions, listVideos } from '@/store/video/video'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  video: {
    type: Object,
    default: null,
  },
  stage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'imported'])

const STAGE_LABEL = { pre: '前测', post: '后测' }

const isScoped = computed(() => Boolean(props.video))
const modalTitle = computed(() => (isScoped.value ? '导入本组题目' : '批量导入题目'))
const scopeHint = computed(() => {
  if (!isScoped.value) return ''
  const stageLabel = STAGE_LABEL[props.stage] ?? ''
  return `当前只导入「${props.video.title}${stageLabel ? ` · ${stageLabel}` : ''}」的题目，其他视频或阶段的题目会校验失败。`
})

const fileInput = ref(null)
const dragging = ref(false)
/** idle | parsing | preview | importing | done | error */
const phase = ref('idle')
const fileName = ref('')
const errorMessage = ref('')
const result = ref(null)
const importedCount = ref(0)

const validCount = computed(() => result.value?.items.length ?? 0)
const errorCount = computed(() => result.value?.errors.length ?? 0)
const hasFile = computed(() =>
  ['parsing', 'preview', 'importing', 'done'].includes(phase.value),
)
const canImport = computed(
  () => phase.value === 'preview' && validCount.value > 0 && errorCount.value === 0,
)
const confirmLabel = computed(() => {
  if (phase.value === 'importing') return '导入中…'
  if (phase.value === 'done') return '完成'
  return `确认导入 ${validCount.value} 道题`
})

const previewRows = computed(() => {
  if (!result.value) return []
  const itemMap = new Map(result.value.items.map((item) => [item.rowNumber, item]))
  const errorMap = new Map(result.value.errors.map((item) => [item.rowNumber, item.messages]))
  const rowNumbers = [...new Set([...itemMap.keys(), ...errorMap.keys()])].sort((a, b) => a - b)
  return rowNumbers.map((rowNumber) => ({
    rowNumber,
    item: itemMap.get(rowNumber) ?? null,
    messages: errorMap.get(rowNumber) ?? [],
  }))
})

watch(
  () => props.open,
  (open) => {
    if (open) reset()
  },
)

function reset() {
  phase.value = 'idle'
  fileName.value = ''
  errorMessage.value = ''
  result.value = null
  importedCount.value = 0
  dragging.value = false
  if (fileInput.value) fileInput.value.value = ''
}

function pickFile() {
  if (phase.value === 'parsing' || phase.value === 'importing') return
  fileInput.value?.click()
}

function onChoose(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (file) handleFile(file)
}

function onDrop(event) {
  dragging.value = false
  if (phase.value === 'parsing' || phase.value === 'importing') return
  const file = event.dataTransfer?.files?.[0]
  if (file) handleFile(file)
}

async function handleFile(file) {
  reset()
  fileName.value = file.name

  if (!/\.(xlsx|xls)$/i.test(file.name)) {
    phase.value = 'error'
    errorMessage.value = '只支持 .xlsx / .xls 格式的文件'
    return
  }

  phase.value = 'parsing'
  try {
    const videos = props.video ? [props.video] : listVideos()
    const parsed = await parseQuestionFile(file, videos, { stage: props.stage || undefined })
    result.value = parsed
    if (!parsed.items.length && !parsed.errors.length) {
      phase.value = 'error'
      errorMessage.value = '没有读取到题目数据，请确认内容填写在「题目模板」表中'
      return
    }
    phase.value = 'preview'
  } catch (error) {
    phase.value = 'error'
    errorMessage.value = error?.message || '文件解析失败，请检查文件后重试'
  }
}

function confirmImport() {
  if (phase.value === 'done') {
    emit('close')
    return
  }
  if (!canImport.value) return

  phase.value = 'importing'
  try {
    const { imported } = importVideoQuestions(result.value.groups)
    importedCount.value = imported
    phase.value = 'done'
    emit('imported', { imported })
  } catch (error) {
    phase.value = 'error'
    errorMessage.value = error?.message || '导入失败，请重试'
  }
}
</script>

<template>
  <div v-if="open" class="import-modal" @click.self="$emit('close')">
    <div
      class="import-modal__dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="import-modal-title"
    >
      <!-- 标题栏 -->
      <header class="import-modal__head">
        <h2 id="import-modal-title" class="import-modal__title">{{ modalTitle }}</h2>
        <button
          type="button"
          class="import-modal__close"
          aria-label="关闭"
          @click="$emit('close')"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            aria-hidden="true"
          >
            <path d="m6 6 12 12M18 6 6 18" />
          </svg>
        </button>
      </header>

      <div class="import-modal__body">
        <!-- 步骤 1：下载模板 -->
        <section class="import-step">
          <div class="import-step__head">
            <span class="import-step__index">1</span>
            <h3 class="import-step__title">下载模板并填写</h3>
          </div>

          <p class="import-step__desc">
            模板只有一张表：「视频标题 + 题目用途（前测/后测）+ 题目内容」。<strong
              class="import-step__strong"
              >视频标题</strong
            >必须与管理端视频列表里的标题完全一致。
          </p>

          <a
            class="btn btn--secondary"
            :href="TEMPLATE_PATH"
            download="科普视频题目_批量导入模板.xlsx"
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
            下载 Excel 模板
          </a>
        </section>

        <hr class="import-modal__divider" />

        <!-- 步骤 2：上传文件 -->
        <section class="import-step">
          <div class="import-step__head">
            <span class="import-step__index">2</span>
            <h3 class="import-step__title">上传填好的文件</h3>
          </div>

          <p v-if="scopeHint" class="import-step__desc">{{ scopeHint }}</p>

          <input
            ref="fileInput"
            class="file-input"
            type="file"
            accept=".xlsx,.xls"
            @change="onChoose"
          />

          <div
            v-if="!hasFile"
            class="dropzone"
            :class="{ 'dropzone--drag': dragging }"
            role="button"
            tabindex="0"
            @click="pickFile"
            @keydown.enter.prevent="pickFile"
            @keydown.space.prevent="pickFile"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
          >
            <svg
              class="dropzone__icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M12 15V4m0 0L7.5 8.5M12 4l4.5 4.5" />
              <path d="M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17" />
            </svg>
            <p class="dropzone__text">拖拽文件到此处，或点击选择文件</p>
            <p class="dropzone__hint">支持 .xlsx / .xls，解析在浏览器本地完成</p>
          </div>

          <div v-else class="file-chip">
            <svg
              class="file-chip__icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
              <path d="M14 3v5h5" />
            </svg>
            <span class="file-chip__name">{{ fileName }}</span>
            <span v-if="phase === 'parsing'" class="file-chip__state">解析中…</span>
            <button
              v-else-if="phase !== 'importing'"
              type="button"
              class="file-chip__change"
              @click="pickFile"
            >
              重新选择
            </button>
          </div>

          <p v-if="phase === 'error'" class="import-error">{{ errorMessage }}</p>
        </section>

        <!-- 步骤 3：校验预览 -->
        <template v-if="result && ['preview', 'importing', 'done'].includes(phase)">
          <hr class="import-modal__divider" />

          <section class="import-step">
            <div class="import-step__head">
              <span class="import-step__index">3</span>
              <h3 class="import-step__title">校验预览</h3>
            </div>

            <p class="import-summary">
              共解析 <strong>{{ validCount + errorCount }}</strong> 行，其中
              <strong class="import-summary__ok">{{ validCount }} 道可导入</strong>
              <template v-if="errorCount">
                ，<strong class="import-summary__bad">{{ errorCount }} 行需修正</strong>
              </template>
            </p>

            <p v-if="errorCount" class="import-tip">
              存在错误的行不会导入，请按提示修正后重新上传。同一视频同一用途会整组替换原有题目。
            </p>

            <div class="preview-table">
              <div class="preview-table__head">
                <span class="pcol pcol--row">行</span>
                <span class="pcol pcol--title">视频标题</span>
                <span class="pcol pcol--stage">用途</span>
                <span class="pcol pcol--order">题序</span>
                <span class="pcol pcol--type">题型</span>
                <span class="pcol pcol--stem">题干</span>
                <span class="pcol pcol--answer">答案</span>
                <span class="pcol pcol--status">状态</span>
              </div>

              <div
                v-for="row in previewRows"
                :key="row.rowNumber"
                class="preview-row"
                :class="{ 'preview-row--error': row.messages.length }"
              >
                <span class="pcol pcol--row">{{ row.rowNumber }}</span>
                <span class="pcol pcol--title" :title="row.item?.videoTitle">
                  {{ row.item?.videoTitle ?? '—' }}
                </span>
                <span class="pcol pcol--stage">
                  {{ row.item ? STAGE_LABEL[row.item.stage] : '—' }}
                </span>
                <span class="pcol pcol--order">{{ row.item?.order ?? '—' }}</span>
                <span class="pcol pcol--type">
                  {{ row.item ? QUESTION_TYPE_LABEL[row.item.type] : '—' }}
                </span>
                <span class="pcol pcol--stem" :title="row.item?.stem">
                  {{ row.item?.stem ?? '—' }}
                </span>
                <span class="pcol pcol--answer">
                  {{ row.item ? String(row.item.answer).replace(/,/g, '') : '—' }}
                </span>
                <span class="pcol pcol--status">
                  {{ row.messages.length ? '需修正' : '可导入' }}
                </span>

                <p v-if="row.messages.length" class="preview-row__errors">
                  {{ row.messages.join('；') }}
                </p>
              </div>
            </div>

            <p v-if="phase === 'done'" class="import-success">
              已成功导入 {{ importedCount }} 道题，视频列表已更新。
            </p>
            <p v-else-if="errorCount" class="import-tip">
              当前有 {{ errorCount }} 行错误，修正前无法提交导入。
            </p>
          </section>
        </template>
      </div>

      <!-- 底部操作 -->
      <footer class="import-modal__foot">
        <button type="button" class="btn btn--secondary" @click="$emit('close')">取消</button>
        <button
          type="button"
          class="btn btn--primary"
          :disabled="!canImport && phase !== 'done'"
          @click="confirmImport"
        >
          {{ confirmLabel }}
        </button>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.import-modal {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-5);
  background: rgb(8 28 21 / 0.45);
  backdrop-filter: blur(2px);
}

.import-modal__dialog {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 720px;
  max-height: calc(100vh - 48px);
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

/* ===== 标题栏 ===== */
.import-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-4);
  padding: var(--sp-5) var(--sp-6) var(--sp-3);
}

.import-modal__title {
  margin: 0;
  font-size: var(--fs-xl);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.import-modal__close {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  padding: 0;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.import-modal__close:hover {
  color: var(--text-primary);
  background: var(--bg-muted);
}

.import-modal__close svg {
  width: 20px;
  height: 20px;
}

/* ===== 内容区 ===== */
.import-modal__body {
  flex: 1;
  padding: var(--sp-2) var(--sp-6) var(--sp-5);
  overflow-y: auto;
}

.import-step {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--sp-3);
  padding: var(--sp-3) 0;
}

.import-step__head {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
}

.import-step__index {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  color: #fff;
  background: var(--brand-primary);
  border-radius: 50%;
}

.import-step__title {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.import-step__desc {
  margin: 0;
  font-size: var(--fs-base);
  line-height: 1.7;
  color: var(--text-secondary);
}

.import-step__strong {
  color: var(--text-primary);
  font-weight: var(--fw-semibold);
}

.import-modal__divider {
  margin: var(--sp-3) 0;
  border: none;
  border-top: 1px solid var(--border);
}

/* ===== 按钮 ===== */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  height: 40px;
  padding: 0 var(--sp-5);
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  text-decoration: none;
  border: 1px solid transparent;
  border-radius: var(--r-md);
  cursor: pointer;
  transition:
    background var(--t-fast),
    box-shadow var(--t-fast),
    transform var(--t-fast);
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

.btn--primary {
  color: #fff;
  background: linear-gradient(
    165deg,
    var(--brand-primary-hover) 0%,
    var(--brand-primary) 55%,
    var(--brand-primary-active) 100%
  );
  box-shadow: 0 8px 20px var(--brand-glow);
}

.btn--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px var(--brand-glow);
}

.btn--primary:disabled {
  background: var(--accent-teal-soft);
  box-shadow: none;
  cursor: not-allowed;
}

/* ===== 上传区 ===== */
.file-input {
  display: none;
}

.dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  width: 100%;
  padding: var(--sp-12) var(--sp-5);
  text-align: center;
  background: var(--brand-tint);
  border: 1.5px dashed rgb(27 67 50 / 0.2);
  border-radius: var(--r-lg);
  cursor: pointer;
  transition:
    border-color var(--t-fast),
    background var(--t-fast);
}

.dropzone:hover,
.dropzone--drag {
  background: var(--brand-soft);
  border-color: var(--brand-primary-hover);
}

.dropzone__icon {
  width: 36px;
  height: 36px;
  margin-bottom: var(--sp-2);
  color: var(--brand-primary-hover);
}

.dropzone__text {
  margin: 0;
  font-size: var(--fs-base);
  color: var(--text-primary);
}

.dropzone__hint {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.file-chip {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  width: 100%;
  padding: var(--sp-3) var(--sp-4);
  background: var(--brand-tint);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
}

.file-chip__icon {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  color: var(--brand-primary-hover);
}

.file-chip__name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  font-size: var(--fs-sm);
  color: var(--text-primary);
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-chip__state {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.file-chip__change {
  flex-shrink: 0;
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  background: none;
  border: none;
  cursor: pointer;
}

.file-chip__change:hover {
  text-decoration: underline;
}

.import-error {
  margin: 0;
  padding: var(--sp-2) var(--sp-3);
  width: 100%;
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-sm);
}

/* ===== 校验预览 ===== */
.import-summary {
  margin: 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.import-summary__ok {
  color: var(--success);
}

.import-summary__bad {
  color: var(--danger-text);
}

.import-tip {
  margin: 0;
  font-size: var(--fs-xs);
  line-height: 1.6;
  color: var(--warning-text);
}

.import-success {
  margin: 0;
  padding: var(--sp-3) var(--sp-4);
  width: 100%;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--success-text);
  background: var(--success-bg);
  border-radius: var(--r-md);
}

.preview-table {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  overflow: hidden;
}

.preview-table__head,
.preview-row {
  display: grid;
  grid-template-columns: 44px minmax(110px, 1.3fr) 56px 48px 72px minmax(150px, 2.2fr) 56px 64px;
  gap: var(--sp-2);
  align-items: center;
  padding: var(--sp-2) var(--sp-3);
}

.preview-table__head {
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-bottom: 1px solid var(--border);
}

.preview-row {
  font-size: var(--fs-xs);
  color: var(--text-primary);
  border-bottom: 1px solid var(--border-light);
}

.preview-row:last-child {
  border-bottom: none;
}

.preview-row--error {
  color: var(--danger-text);
  background: var(--danger-bg);
}

.pcol {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pcol--row,
.pcol--order,
.pcol--answer {
  text-align: center;
}

.pcol--status {
  font-weight: var(--fw-semibold);
}

.preview-row--error .pcol--status {
  color: var(--danger-text);
}

.preview-row:not(.preview-row--error) .pcol--status {
  color: var(--success);
}

.preview-row__errors {
  grid-column: 1 / -1;
  margin: var(--sp-1) 0 0;
  font-size: var(--fs-xs);
  line-height: 1.5;
  color: var(--danger-text);
}

/* ===== 底部操作 ===== */
.import-modal__foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-3);
  padding: var(--sp-4) var(--sp-6);
  border-top: 1px solid var(--border);
}

/* ===== 窄屏 ===== */
@media (max-width: 640px) {
  .import-modal {
    padding: var(--sp-3);
  }

  .import-modal__head,
  .import-modal__body,
  .import-modal__foot {
    padding-left: var(--sp-4);
    padding-right: var(--sp-4);
  }

  .dropzone {
    padding: var(--sp-8) var(--sp-4);
  }

  .preview-table {
    overflow-x: auto;
  }

  .preview-table__head,
  .preview-row {
    min-width: 620px;
  }
}
</style>
