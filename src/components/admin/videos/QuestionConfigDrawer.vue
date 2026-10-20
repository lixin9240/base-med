<script setup>
import { ref, watch } from 'vue'
import { QUESTION_TYPE_LABEL, optionLetter } from '@/utils/question'
import { TEMPLATE_PATH } from '@/utils/video/questionImport'
import { exportQuestionGroup } from '@/utils/video/questionExport'
import {
  REQUIRED_PER_STAGE,
  listVideoQuestions,
  removeVideoQuestion,
  saveVideoQuestion,
} from '@/store/video/video'
import QuestionEditorModal from './QuestionEditorModal.vue'
import QuestionImportModal from './QuestionImportModal.vue'

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

const emit = defineEmits(['close', 'changed'])

const STAGES = [
  { value: 'pre', label: '前测题目' },
  { value: 'post', label: '后测题目' },
]

const activeStage = ref('pre')
const counts = ref({ pre: 0, post: 0 })
const questions = ref([])
const deletingId = ref('')
const exporting = ref(false)

const editorOpen = ref(false)
const editingQuestion = ref(null)
const importOpen = ref(false)

watch(
  () => [props.open, props.video?.id],
  ([open]) => {
    if (!open) return
    activeStage.value = 'pre'
    deletingId.value = ''
    load()
  },
)

watch(activeStage, () => {
  deletingId.value = ''
  load()
})

function load() {
  if (!props.video) {
    counts.value = { pre: 0, post: 0 }
    questions.value = []
    return
  }

  counts.value = {
    pre: listVideoQuestions(props.video.id, 'pre').length,
    post: listVideoQuestions(props.video.id, 'post').length,
  }
  questions.value = listVideoQuestions(props.video.id, activeStage.value)
}

function countTone(count) {
  if (count >= REQUIRED_PER_STAGE) return 'success'
  return count > 0 ? 'warning' : 'danger'
}

function isCorrect(question, index) {
  const letter = optionLetter(index)
  return Array.isArray(question.answer)
    ? question.answer.includes(letter)
    : question.answer === letter
}

function openCreate() {
  editingQuestion.value = null
  editorOpen.value = true
}

function openEdit(question) {
  editingQuestion.value = question
  editorOpen.value = true
}

function onSave(payload) {
  if (!props.video) return

  const question = editingQuestion.value
    ? { ...payload, id: editingQuestion.value.id }
    : payload

  saveVideoQuestion(props.video.id, activeStage.value, question)
  editorOpen.value = false
  editingQuestion.value = null
  load()
  emit('changed')
}

function askDelete(question) {
  deletingId.value = question.id
}

function cancelDelete() {
  deletingId.value = ''
}

function confirmDelete(question) {
  if (!props.video) return
  removeVideoQuestion(props.video.id, activeStage.value, question.id)
  deletingId.value = ''
  load()
  emit('changed')
}

function onImported() {
  load()
  emit('changed')
}

async function onExport() {
  if (!props.video || !questions.value.length || exporting.value) return
  exporting.value = true
  try {
    await exportQuestionGroup({
      videoTitle: props.video.title,
      stage: activeStage.value,
      questions: questions.value,
    })
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <Transition name="drawer">
    <div v-if="open" class="drawer-mask" @click.self="$emit('close')">
      <aside class="drawer" role="dialog" aria-modal="true" :aria-label="`题目配置：${video?.title ?? ''}`">
        <header class="drawer__head">
          <div class="drawer__intro">
            <h2 class="drawer__title">{{ video?.title }}</h2>
            <p class="drawer__sub">配置前测 / 后测题库</p>
          </div>
          <button type="button" class="drawer__close" aria-label="关闭" @click="$emit('close')">
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

        <div class="drawer__tabs" role="tablist" aria-label="题目阶段">
          <button
            v-for="stage in STAGES"
            :key="stage.value"
            type="button"
            role="tab"
            class="stage-tab"
            :class="{ 'stage-tab--active': activeStage === stage.value }"
            :aria-selected="activeStage === stage.value"
            @click="activeStage = stage.value"
          >
            {{ stage.label }}
            <span class="stage-tab__count" :class="`stage-tab__count--${countTone(counts[stage.value])}`">
              {{ counts[stage.value] }}/{{ REQUIRED_PER_STAGE }}
            </span>
          </button>
        </div>

        <div class="drawer__actions">
          <button type="button" class="btn btn--primary" @click="openCreate">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <path d="M12 5v14M5 12h14" />
            </svg>
            新增题目
          </button>

          <a class="btn btn--secondary" :href="TEMPLATE_PATH" download="科普视频题目_批量导入模板.xlsx">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 4v11m0 0 4.5-4.5M12 15l-4.5-4.5" />
              <path d="M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17" />
            </svg>
            下载模板
          </a>

          <button type="button" class="btn btn--secondary" @click="importOpen = true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 15V4m0 0L7.5 8.5M12 4l4.5 4.5" />
              <path d="M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17" />
            </svg>
            导入本组
          </button>

          <button
            type="button"
            class="btn btn--secondary"
            :disabled="!questions.length || exporting"
            @click="onExport"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
              <path d="M14 3v5h5" />
              <path d="M12 11v6m0 0 2.5-2.5M12 17l-2.5-2.5" />
            </svg>
            导出本组
          </button>
        </div>

        <hr class="drawer__divider" />

        <div class="drawer__body">
          <template v-if="questions.length">
            <article v-for="question in questions" :key="question.id" class="q-card">
              <div class="q-card__head">
                <span class="q-card__type">{{ QUESTION_TYPE_LABEL[question.type] }}</span>
                <span class="q-card__order">第 {{ question.order }} 题</span>
                <div class="q-card__actions">
                  <button type="button" class="link-btn" @click="openEdit(question)">编辑</button>
                  <button type="button" class="link-btn link-btn--danger" @click="askDelete(question)">
                    删除
                  </button>
                </div>
              </div>

              <p class="q-card__stem">{{ question.stem }}</p>

              <ul class="q-options">
                <li
                  v-for="(option, index) in question.options"
                  :key="index"
                  class="q-option"
                  :class="{ 'q-option--correct': isCorrect(question, index) }"
                >
                  <span class="q-option__letter">{{ optionLetter(index) }}</span>
                  <span class="q-option__text">{{ option }}</span>
                  <span v-if="isCorrect(question, index)" class="q-option__flag">正确答案</span>
                </li>
              </ul>

              <p v-if="question.analysis" class="q-card__analysis">
                解析：{{ question.analysis }}
              </p>

              <div v-if="deletingId === question.id" class="q-card__confirm">
                <span>确定删除这道题吗？</span>
                <button type="button" class="link-btn link-btn--danger" @click="confirmDelete(question)">
                  确认删除
                </button>
                <button type="button" class="link-btn" @click="cancelDelete">取消</button>
              </div>
            </article>
          </template>

          <div v-else class="drawer-empty">
            <svg
              class="drawer-empty__icon"
              viewBox="0 0 48 48"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              aria-hidden="true"
            >
              <path d="m10 14 4 4 7-8M10 26l4 4 7-8M10 38l4 4 7-8" />
              <path d="M28 14h10M28 26h10M28 38h10" />
            </svg>
            <p class="drawer-empty__text">
              还没有{{ activeStage === 'pre' ? '前测' : '后测' }}题目。
            </p>
            <p class="drawer-empty__hint">
              建议配 {{ REQUIRED_PER_STAGE }} 道题，也可以用 Excel 批量导入。
            </p>
            <button type="button" class="btn btn--primary" @click="openCreate">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
              </svg>
              新增题目
            </button>
          </div>
        </div>
      </aside>
    </div>
  </Transition>

  <QuestionEditorModal
    :open="editorOpen"
    :mode="editingQuestion ? 'edit' : 'create'"
    :stage="activeStage"
    :question="editingQuestion"
    @close="editorOpen = false"
    @save="onSave"
  />

  <QuestionImportModal
    :open="importOpen"
    :video="video"
    :stage="activeStage"
    @close="importOpen = false"
    @imported="onImported"
  />
</template>

<style scoped>
.drawer-mask {
  position: fixed;
  inset: 0;
  z-index: 900;
  display: flex;
  justify-content: flex-end;
  background: rgb(8 28 21 / 0.45);
  backdrop-filter: blur(2px);
}

.drawer {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 620px;
  height: 100%;
  background: var(--bg-surface-solid);
  box-shadow: var(--shadow-lg);
}

/* ===== 头部 ===== */
.drawer__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--sp-4);
  padding: var(--sp-5) var(--sp-6) var(--sp-3);
}

.drawer__intro {
  display: flex;
  flex-direction: column;
  gap: var(--sp-1);
  min-width: 0;
}

.drawer__title {
  margin: 0;
  font-size: var(--fs-xl);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drawer__sub {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.drawer__close {
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

.drawer__close:hover {
  color: var(--text-primary);
  background: var(--bg-muted);
}

.drawer__close svg {
  width: 20px;
  height: 20px;
}

/* ===== 阶段 tab ===== */
.drawer__tabs {
  display: flex;
  gap: var(--sp-3);
  padding: var(--sp-2) var(--sp-6) var(--sp-3);
}

.stage-tab {
  display: inline-flex;
  align-items: center;
  gap: var(--sp-2);
  height: 40px;
  padding: 0 var(--sp-4);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  cursor: pointer;
  transition:
    color var(--t-fast),
    background var(--t-fast),
    border-color var(--t-fast);
}

.stage-tab:hover {
  border-color: var(--brand-primary-hover);
}

.stage-tab--active {
  color: var(--brand-primary-hover);
  background: var(--brand-tint);
  border-color: var(--brand-primary-hover);
}

.stage-tab__count {
  padding: 2px 8px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  border-radius: var(--r-full);
}

.stage-tab__count--success {
  color: var(--success-text);
  background: var(--success-bg);
}

.stage-tab__count--warning {
  color: var(--warning-text);
  background: var(--warning-bg);
}

.stage-tab__count--danger {
  color: var(--danger-text);
  background: var(--danger-bg);
}

/* ===== 操作区 ===== */
.drawer__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--sp-2);
  padding: 0 var(--sp-6) var(--sp-4);
}

.drawer__divider {
  margin: 0;
  border: none;
  border-top: 1px solid var(--border);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  height: 40px;
  padding: 0 var(--sp-4);
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

.btn svg {
  width: 16px;
  height: 16px;
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

.btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px var(--brand-glow);
}

.btn--secondary {
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border-color: var(--border);
  box-shadow: var(--shadow-sm);
}

.btn--secondary:hover:not(:disabled) {
  background: var(--bg-muted);
}

.btn--secondary:disabled {
  color: var(--text-disabled);
  cursor: not-allowed;
}

/* ===== 列表 ===== */
.drawer__body {
  flex: 1;
  min-height: 0;
  padding: var(--sp-4) var(--sp-6) var(--sp-6);
  overflow-y: auto;
}

.q-card {
  display: flex;
  flex-direction: column;
  gap: var(--sp-3);
  padding: var(--sp-4);
  margin-bottom: var(--sp-3);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm);
}

.q-card__head {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
}

.q-card__type {
  padding: 2px 10px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--info-text);
  background: var(--info-bg);
  border-radius: var(--r-full);
}

.q-card__order {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.q-card__actions {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  margin-left: auto;
}

.q-card__stem {
  margin: 0;
  font-size: var(--fs-base);
  font-weight: var(--fw-medium);
  line-height: 1.6;
  color: var(--text-primary);
}

.q-options {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
  margin: 0;
  padding: 0;
  list-style: none;
}

.q-option {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  padding: 6px 10px;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border: 1px solid transparent;
  border-radius: var(--r-sm);
}

.q-option--correct {
  color: var(--success-text);
  background: var(--success-bg);
  border-color: var(--success);
}

.q-option__letter {
  flex-shrink: 0;
  font-weight: var(--fw-semibold);
}

.q-option__text {
  flex: 1;
  min-width: 0;
}

.q-option__flag {
  flex-shrink: 0;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
}

.q-card__analysis {
  margin: 0;
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-xs);
  line-height: 1.6;
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-radius: var(--r-sm);
}

.q-card__confirm {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-xs);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-sm);
}

.link-btn {
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  background: none;
  border: none;
  cursor: pointer;
  white-space: nowrap;
}

.link-btn:hover {
  text-decoration: underline;
}

.link-btn--danger {
  color: var(--danger-text);
}

/* ===== 空状态 ===== */
.drawer-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  min-height: 380px;
  padding: var(--sp-8) var(--sp-5);
  text-align: center;
}

.drawer-empty__icon {
  width: 56px;
  height: 56px;
  margin-bottom: var(--sp-2);
  color: var(--text-disabled);
}

.drawer-empty__text {
  margin: 0;
  font-size: var(--fs-lg);
  color: var(--text-primary);
}

.drawer-empty__hint {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

/* ===== 抽屉动画 ===== */
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity var(--t-fast);
}

.drawer-enter-active .drawer,
.drawer-leave-active .drawer {
  transition: transform var(--t-fast);
}

.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}

.drawer-enter-from .drawer,
.drawer-leave-to .drawer {
  transform: translateX(100%);
}

/* ===== 窄屏 ===== */
@media (max-width: 640px) {
  .drawer {
    max-width: none;
  }

  .drawer__head,
  .drawer__tabs,
  .drawer__actions,
  .drawer__body {
    padding-left: var(--sp-4);
    padding-right: var(--sp-4);
  }
}
</style>
