<script setup>
import { computed, ref, watch } from 'vue'
import { QUESTION_TYPE_LABEL } from '@/utils/question'
import {
  ANALYSIS_MAX,
  MAX_OPTIONS,
  MIN_OPTIONS,
  STEM_MAX,
  createQuestionDraft,
  draftToQuestion,
  questionToDraft,
  validateQuestionDraft,
} from '@/utils/video/questionDraft'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: 'create',
  },
  stage: {
    type: String,
    default: 'pre',
  },
  question: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'save'])

const TYPES = ['single', 'multiple', 'judge']
const STAGE_LABEL = { pre: '前测', post: '后测' }

const draft = ref(createQuestionDraft())
const errors = ref({})

const isJudge = computed(() => draft.value.type === 'judge')
const title = computed(() => (props.mode === 'edit' ? '编辑题目' : '新增题目'))
const canAddOption = computed(
  () => !isJudge.value && draft.value.options.length < MAX_OPTIONS,
)
const canRemoveOption = computed(
  () => !isJudge.value && draft.value.options.length > MIN_OPTIONS,
)

watch(
  () => props.open,
  (open) => {
    if (!open) return
    draft.value = props.question ? questionToDraft(props.question) : createQuestionDraft('single')
    errors.value = {}
  },
)

function optionLetter(index) {
  return String.fromCharCode(65 + index)
}

function setType(type) {
  if (draft.value.type === type) return
  const next = createQuestionDraft(type)
  next.stem = draft.value.stem
  next.analysis = draft.value.analysis
  draft.value = next
  errors.value = {}
}

function addOption() {
  if (!canAddOption.value) return
  draft.value.options.push('')
}

function removeOption() {
  if (!canRemoveOption.value) return
  const removedIndex = draft.value.options.length - 1
  draft.value.options.pop()
  draft.value.correct = draft.value.correct.filter((index) => index < removedIndex)
}

function isCorrect(index) {
  return draft.value.correct.includes(index)
}

function toggleCorrect(index) {
  if (isJudge.value || draft.value.type === 'single') {
    draft.value.correct = isCorrect(index) ? [] : [index]
    return
  }

  draft.value.correct = isCorrect(index)
    ? draft.value.correct.filter((item) => item !== index)
    : [...draft.value.correct, index].sort((a, b) => a - b)
}

function save() {
  const found = validateQuestionDraft(draft.value)
  errors.value = found
  if (Object.keys(found).length) return
  emit('save', draftToQuestion(draft.value))
}
</script>

<template>
  <div v-if="open" class="editor-mask" @click.self="$emit('close')">
    <div class="editor" role="dialog" aria-modal="true" aria-labelledby="editor-title">
      <header class="editor__head">
        <h2 id="editor-title" class="editor__title">{{ title }}</h2>
        <span class="editor__stage">{{ STAGE_LABEL[stage] }}</span>
        <button type="button" class="editor__close" aria-label="关闭" @click="$emit('close')">
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

      <div class="editor__body">
        <!-- 题型 -->
        <div class="field field--row">
          <span class="field__label"><i class="field__req">*</i>题型</span>
          <div class="field__control">
            <div class="segmented" role="tablist" aria-label="题型">
              <button
                v-for="type in TYPES"
                :key="type"
                type="button"
                role="tab"
                class="segmented__item"
                :class="{ 'segmented__item--active': draft.type === type }"
                :aria-selected="draft.type === type"
                @click="setType(type)"
              >
                {{ QUESTION_TYPE_LABEL[type] }}
              </button>
            </div>
          </div>
        </div>

        <!-- 题干 -->
        <div class="field field--row">
          <label class="field__label" for="question-stem"><i class="field__req">*</i>题干</label>
          <div class="field__control">
            <textarea
              id="question-stem"
              v-model="draft.stem"
              class="textarea"
              :class="{ 'textarea--error': errors.stem }"
              :maxlength="STEM_MAX"
              rows="4"
              placeholder="请输入题干"
            ></textarea>
            <div class="field__meta">
              <span v-if="errors.stem" class="field__error">{{ errors.stem }}</span>
              <span class="counter">{{ draft.stem.length }} / {{ STEM_MAX }}</span>
            </div>
          </div>
        </div>

        <!-- 选项 -->
        <div class="field field--row">
          <span class="field__label"><i class="field__req">*</i>选项</span>
          <div class="field__control">
            <div
              v-for="(option, index) in draft.options"
              :key="index"
              class="option-row"
            >
              <span class="option-row__letter">{{ optionLetter(index) }}</span>
              <input
                v-model="draft.options[index]"
                class="input"
                :disabled="isJudge"
                :placeholder="
                  isJudge
                    ? ''
                    : `选项 ${optionLetter(index)}${index < 2 ? '（必填）' : '（选填）'}`
                "
              />
              <button
                type="button"
                class="option-row__check"
                :class="{ 'option-row__check--on': isCorrect(index) }"
                role="checkbox"
                :aria-checked="isCorrect(index)"
                :aria-label="`将选项 ${optionLetter(index)} 标记为正确答案`"
                @click="toggleCorrect(index)"
              >
                <svg
                  viewBox="0 0 16 16"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  aria-hidden="true"
                >
                  <path d="m3.5 8.5 3 3 6-7" />
                </svg>
              </button>
            </div>

            <div v-if="!isJudge" class="option-actions">
              <button
                type="button"
                class="link-action"
                :disabled="!canAddOption"
                @click="addOption"
              >
                + 添加选项
              </button>
              <button
                type="button"
                class="link-action link-action--danger"
                :disabled="!canRemoveOption"
                @click="removeOption"
              >
                − 移除末项
              </button>
            </div>

            <p v-if="errors.options" class="field__error">{{ errors.options }}</p>
            <p v-if="errors.answer" class="field__error">{{ errors.answer }}</p>
            <p class="field__hint">勾选右侧方块标记正确答案</p>
          </div>
        </div>

        <!-- 解析 -->
        <div class="field field--row">
          <label class="field__label" for="question-analysis">答案解析</label>
          <div class="field__control">
            <textarea
              id="question-analysis"
              v-model="draft.analysis"
              class="textarea"
              :class="{ 'textarea--error': errors.analysis }"
              :maxlength="ANALYSIS_MAX"
              rows="3"
              placeholder="选填，答题后展示给用户"
            ></textarea>
            <div class="field__meta">
              <span v-if="errors.analysis" class="field__error">{{ errors.analysis }}</span>
              <span class="counter">{{ draft.analysis.length }} / {{ ANALYSIS_MAX }}</span>
            </div>
          </div>
        </div>
      </div>

      <footer class="editor__foot">
        <button type="button" class="btn btn--secondary" @click="$emit('close')">取消</button>
        <button type="button" class="btn btn--primary" @click="save">保存</button>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.editor-mask {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-5);
  background: rgb(8 28 21 / 0.45);
  backdrop-filter: blur(2px);
}

.editor {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 680px;
  max-height: calc(100vh - 48px);
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

/* ===== 标题栏 ===== */
.editor__head {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  padding: var(--sp-5) var(--sp-6) var(--sp-3);
}

.editor__title {
  margin: 0;
  font-size: var(--fs-xl);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.editor__stage {
  padding: 2px 10px;
  font-size: var(--fs-xs);
  color: var(--brand-primary-hover);
  background: var(--brand-tint);
  border-radius: var(--r-full);
}

.editor__close {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  margin-left: auto;
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

.editor__close:hover {
  color: var(--text-primary);
  background: var(--bg-muted);
}

.editor__close svg {
  width: 20px;
  height: 20px;
}

/* ===== 表单 ===== */
.editor__body {
  flex: 1;
  padding: var(--sp-2) var(--sp-6) var(--sp-4);
  overflow-y: auto;
}

.field {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
  padding: var(--sp-3) 0;
}

.field--row {
  flex-direction: row;
  align-items: flex-start;
  gap: var(--sp-4);
}

.field__label {
  flex-shrink: 0;
  width: 76px;
  padding-top: 9px;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-primary);
}

.field__req {
  margin-right: 2px;
  color: var(--danger);
  font-style: normal;
}

.field__control {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
}

.textarea,
.input {
  width: 100%;
  font-family: inherit;
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  outline: none;
  transition: border-color var(--t-fast);
}

.textarea {
  padding: var(--sp-3);
  line-height: 1.6;
  resize: vertical;
}

.input {
  height: 40px;
  padding: 0 var(--sp-3);
}

.textarea:focus,
.input:focus {
  border-color: var(--brand-primary-hover);
}

.input:disabled {
  color: var(--text-secondary);
  background: var(--bg-muted);
}

.textarea--error,
.input--error {
  border-color: var(--danger);
}

.field__meta {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-3);
  margin-top: -2px;
}

.field__error {
  flex: 1;
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--danger-text);
}

.field__hint {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.counter {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

/* ===== 题型切换 ===== */
.segmented {
  display: inline-flex;
  padding: 2px;
  background: var(--bg-muted);
  border-radius: var(--r-md);
}

.segmented__item {
  height: 34px;
  padding: 0 var(--sp-4);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: transparent;
  border: none;
  border-radius: calc(var(--r-md) - 2px);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.segmented__item--active {
  color: #fff;
  background: var(--brand-primary);
  box-shadow: var(--shadow-sm);
}

/* ===== 选项 ===== */
.option-row {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
}

.option-row__letter {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-radius: 50%;
}

.option-row__check {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  padding: 0;
  color: transparent;
  background: var(--bg-surface-solid);
  border: 1.5px solid var(--border);
  border-radius: 50%;
  cursor: pointer;
  transition:
    background var(--t-fast),
    border-color var(--t-fast),
    color var(--t-fast);
}

.option-row__check svg {
  width: 13px;
  height: 13px;
}

.option-row__check:hover {
  border-color: var(--brand-primary-hover);
}

.option-row__check--on {
  color: #fff;
  background: var(--brand-primary);
  border-color: var(--brand-primary);
}

.option-actions {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
  padding-left: 36px;
}

.link-action {
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  background: none;
  border: none;
  cursor: pointer;
}

.link-action:disabled {
  color: var(--text-disabled);
  cursor: not-allowed;
}

.link-action--danger {
  color: var(--danger-text);
}

/* ===== 底部 ===== */
.editor__foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-3);
  padding: var(--sp-4) var(--sp-6);
  border-top: 1px solid var(--border);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 40px;
  padding: 0 var(--sp-5);
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  border: 1px solid transparent;
  border-radius: var(--r-md);
  cursor: pointer;
  transition:
    background var(--t-fast),
    box-shadow var(--t-fast),
    transform var(--t-fast);
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

.btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px var(--brand-glow);
}

/* ===== 窄屏 ===== */
@media (max-width: 640px) {
  .editor-mask {
    padding: var(--sp-3);
  }

  .editor__head,
  .editor__body,
  .editor__foot {
    padding-left: var(--sp-4);
    padding-right: var(--sp-4);
  }

  .field--row {
    flex-direction: column;
    gap: var(--sp-2);
  }

  .field__label {
    width: auto;
    padding-top: 0;
  }
}
</style>
