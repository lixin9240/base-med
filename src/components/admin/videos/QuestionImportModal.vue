<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { downloadBlob } from '@/utils/download'
import { toast } from '@/utils/toast'
import {
  confirmAdminImportTask,
  downloadQuestionTemplate,
  getAdminImportTaskDetail,
  importAdminVideoQuestions,
  listAdminVideos,
} from '@/api'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  video: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'imported'])

const POLL_INTERVAL = 1500
const POLL_TIMEOUT = 90_000

const isScoped = computed(() => Boolean(props.video))
const modalTitle = computed(() => (isScoped.value ? '导入本组题目' : '批量导入题目'))

// 未从抽屉进入时没有 video，需要自行选择（接口要求 video_id）
const videoOptions = ref([])
const loadingVideos = ref(false)
const selectedVideoId = ref('')

const scopeVideo = computed(() => {
  if (props.video) return props.video
  return (
    videoOptions.value.find((item) => String(item.id) === String(selectedVideoId.value)) ?? null
  )
})

const scopeHint = computed(() => {
  if (!scopeVideo.value) return ''
  return `当前只导入「${scopeVideo.value.title}」的题目。`
})

const fileInput = ref(null)
const dragging = ref(false)
/** idle | uploading | validating | preview | confirming | done | error */
const phase = ref('idle')
const fileName = ref('')
const errorMessage = ref('')
const task = ref(null)
const taskId = ref(0)
const downloadingTemplate = ref(false)

let pollTimer = null
let pollStartedAt = 0

const busy = computed(() => ['uploading', 'validating', 'confirming'].includes(phase.value))
const hasFile = computed(() => Boolean(fileName.value))
const counts = computed(() => ({
  total: Number(task.value?.total_rows) || 0,
  valid: Number(task.value?.valid_rows) || 0,
  errors: Number(task.value?.error_rows) || 0,
}))
const errorReport = computed(() =>
  Array.isArray(task.value?.error_report) ? task.value.error_report : [],
)
const canConfirm = computed(
  () => phase.value === 'preview' && task.value?.status === 2 && counts.value.errors === 0,
)
const confirmLabel = computed(() => {
  if (phase.value === 'uploading') return '上传中…'
  if (phase.value === 'validating') return '校验中…'
  if (phase.value === 'confirming') return '导入中…'
  if (phase.value === 'done') return '完成'
  return counts.value.valid ? `确认导入 ${counts.value.valid} 道题` : '确认导入'
})

watch(
  () => props.open,
  (open) => {
    if (open) {
      reset()
      if (!props.video) loadVideoOptions()
    } else {
      stopPolling()
    }
  },
)

watch(selectedVideoId, () => {
  if (!isScoped.value && phase.value !== 'idle') reset()
})

onBeforeUnmount(stopPolling)

function reset() {
  stopPolling()
  phase.value = 'idle'
  fileName.value = ''
  errorMessage.value = ''
  task.value = null
  taskId.value = 0
  dragging.value = false
  if (fileInput.value) fileInput.value.value = ''
}

async function loadVideoOptions() {
  if (loadingVideos.value || videoOptions.value.length) return
  loadingVideos.value = true
  try {
    // 后端 page_size 上限 100（VideoListRequest），逐页取全量供下拉选择
    const pageSize = 100
    const collected = []
    let page = 1
    let total = 0
    do {
      const result = await listAdminVideos({ page, pageSize })
      collected.push(...result.list)
      total = result.total
      page += 1
      if (!result.list.length) break
    } while (collected.length < total)
    videoOptions.value = collected
  } catch (error) {
    toast(error?.message || '视频列表加载失败')
  } finally {
    loadingVideos.value = false
  }
}

function stopPolling() {
  if (pollTimer) {
    clearTimeout(pollTimer)
    pollTimer = null
  }
}

function schedulePoll() {
  pollTimer = setTimeout(tickPoll, POLL_INTERVAL)
}

function startPolling() {
  stopPolling()
  pollStartedAt = Date.now()
  schedulePoll()
}

async function tickPoll() {
  pollTimer = null
  try {
    const detail = await getAdminImportTaskDetail({ taskId: taskId.value })
    task.value = detail

    if (detail.status === 2) {
      phase.value = 'preview'
      return
    }
    if (detail.status === 4) {
      phase.value = 'done'
      emit('imported', { imported: counts.value.valid })
      return
    }
    if (detail.status === 5) {
      phase.value = 'error'
      errorMessage.value = '导入任务处理失败，请检查文件内容后重试'
      return
    }
    if (Date.now() - pollStartedAt > POLL_TIMEOUT) {
      phase.value = 'error'
      errorMessage.value = '任务处理超时，请稍后在导入任务中查看结果'
      return
    }
    schedulePoll()
  } catch (error) {
    phase.value = 'error'
    errorMessage.value = error?.message || '导入任务查询失败'
  }
}

function pickFile() {
  if (busy.value) return
  fileInput.value?.click()
}

function onChoose(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (file) handleFile(file)
}

function onDrop(event) {
  dragging.value = false
  if (busy.value) return
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
  if (!scopeVideo.value) {
    phase.value = 'error'
    errorMessage.value = '请先选择要导入的视频'
    return
  }

  phase.value = 'uploading'
  try {
    const created = await importAdminVideoQuestions({
      // 从抽屉进入时 scopeVideo 是本地记录，真实 video_id 在 apiId 上
      videoId: scopeVideo.value.apiId ?? scopeVideo.value.id,
      file,
    })
    taskId.value = created.task_id
    task.value = created
    phase.value = 'validating'
    startPolling()
  } catch (error) {
    phase.value = 'error'
    errorMessage.value = error?.message || '上传失败，请重试'
  }
}

async function onDownloadTemplate() {
  if (downloadingTemplate.value) return
  downloadingTemplate.value = true
  try {
    const blob = await downloadQuestionTemplate()
    downloadBlob(blob, '科普视频题目_批量导入模板.xlsx')
  } catch (error) {
    toast(error?.message || '模板下载失败，请重试')
  } finally {
    downloadingTemplate.value = false
  }
}

async function confirmImport() {
  if (phase.value === 'done') {
    emit('close')
    return
  }
  if (!canConfirm.value) return

  phase.value = 'confirming'
  try {
    await confirmAdminImportTask({ taskId: taskId.value })
    startPolling()
  } catch (error) {
    phase.value = 'error'
    errorMessage.value = error?.message || '确认导入失败，请重试'
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
        <button type="button" class="import-modal__close" aria-label="关闭" @click="$emit('close')">
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
            模板中的题目前后测共用，按列填写题干、选项与正确答案即可。
          </p>

          <button
            type="button"
            class="btn btn--secondary"
            :disabled="downloadingTemplate"
            @click="onDownloadTemplate"
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
            {{ downloadingTemplate ? '下载中…' : '下载 Excel 模板' }}
          </button>
        </section>

        <hr class="import-modal__divider" />

        <!-- 步骤 2：上传文件 -->
        <section class="import-step">
          <div class="import-step__head">
            <span class="import-step__index">2</span>
            <h3 class="import-step__title">上传填好的文件</h3>
          </div>

          <div v-if="!isScoped" class="scope-fields">
            <label class="scope-field">
              <span class="scope-field__label">视频</span>
              <select v-model="selectedVideoId" class="scope-field__select" :disabled="busy">
                <option value="">{{ loadingVideos ? '加载中…' : '请选择视频' }}</option>
                <option v-for="item in videoOptions" :key="item.id" :value="item.id">
                  {{ item.title }}
                </option>
              </select>
            </label>
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
            <p class="dropzone__hint">支持 .xlsx / .xls，上传后由服务端校验</p>
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
            <span v-if="phase === 'uploading'" class="file-chip__state">上传中…</span>
            <span v-else-if="phase === 'validating'" class="file-chip__state">校验中…</span>
            <span v-else-if="phase === 'confirming'" class="file-chip__state">导入中…</span>
            <button
              v-else-if="phase !== 'done'"
              type="button"
              class="file-chip__change"
              @click="pickFile"
            >
              重新选择
            </button>
          </div>

          <p v-if="phase === 'error'" class="import-error">{{ errorMessage }}</p>
        </section>

        <!-- 步骤 3：校验结果 -->
        <template v-if="task && ['preview', 'confirming', 'done'].includes(phase)">
          <hr class="import-modal__divider" />

          <section class="import-step">
            <div class="import-step__head">
              <span class="import-step__index">3</span>
              <h3 class="import-step__title">校验结果</h3>
            </div>

            <p class="import-summary">
              共校验 <strong>{{ counts.total }}</strong> 行，其中
              <strong class="import-summary__ok">{{ counts.valid }} 行可导入</strong>
              <template v-if="counts.errors">
                ，<strong class="import-summary__bad">{{ counts.errors }} 行需修正</strong>
              </template>
            </p>

            <p v-if="counts.errors" class="import-tip">
              存在错误的行不会导入，请按提示修正后重新上传。
            </p>

            <div v-if="errorReport.length" class="error-list">
              <div class="error-list__head">
                <span>行</span>
                <span>工作表</span>
                <span>错误信息</span>
              </div>
              <div v-for="(item, index) in errorReport" :key="index" class="error-list__row">
                <span>{{ item.row }}</span>
                <span>{{ item.sheet }}</span>
                <span :title="item.message">{{ item.message }}</span>
              </div>
            </div>

            <p v-if="phase === 'done'" class="import-success">
              已成功导入 {{ counts.valid }} 道题，题目列表已更新。
            </p>
            <p v-else-if="counts.errors" class="import-tip">
              当前有 {{ counts.errors }} 行错误，修正前无法提交导入。
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
          :disabled="!canConfirm && phase !== 'done'"
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

.error-list {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  overflow: hidden;
}

.error-list__head,
.error-list__row {
  display: grid;
  grid-template-columns: 56px 96px minmax(0, 1fr);
  gap: var(--sp-2);
  align-items: center;
  padding: var(--sp-2) var(--sp-3);
}

.error-list__head {
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-bottom: 1px solid var(--border);
}

.error-list__row {
  font-size: var(--fs-xs);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-bottom: 1px solid var(--border-light);
}

.error-list__row:last-child {
  border-bottom: none;
}

.error-list__row > span {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.error-list__head > span:first-child,
.error-list__row > span:first-child {
  text-align: center;
}

/* ===== 视频 / 用途选择（列表页批量导入） ===== */
.scope-fields {
  display: flex;
  flex-wrap: wrap;
  gap: var(--sp-4);
  width: 100%;
}

.scope-field {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
}

.scope-field__label {
  flex-shrink: 0;
  font-size: var(--fs-sm);
  color: var(--text-primary);
}

.scope-field__select {
  height: 36px;
  min-width: 180px;
  padding: 0 var(--sp-3);
  font-family: inherit;
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  outline: none;
}

.scope-field__select:focus {
  border-color: var(--brand-primary-hover);
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

  .scope-fields {
    flex-direction: column;
    gap: var(--sp-2);
  }

  .scope-field__select {
    flex: 1;
    min-width: 0;
  }
}
</style>
