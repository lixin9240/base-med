<script setup>
import { computed, ref, watch } from 'vue'
import AppIcon from '@/components/mobile/AppIcon.vue'
import { QUESTION_TYPE_LABEL, hasAnswer, optionLetter } from '@/utils/video/question'
import { toast } from '@/utils/toast'

const props = defineProps({
  questions: { type: Array, required: true },
  stageLabel: { type: String, default: '答题' },
})

const emit = defineEmits(['submit', 'change'])

const index = ref(0)
const answers = ref(props.questions.map(() => null))

const current = computed(() => props.questions[index.value])
const progress = computed(() => ((index.value + 1) / props.questions.length) * 100)

watch(
  answers,
  () => emit('change', answers.value.filter((a) => hasAnswer(a)).length),
  { deep: true, immediate: true },
)

function isSelected(optIndex) {
  const val = answers.value[index.value]
  const letter = optionLetter(optIndex)
  if (Array.isArray(val)) return val.includes(letter)
  return val === letter
}

function pick(optIndex) {
  const letter = optionLetter(optIndex)
  if (current.value.type === 'multiple') {
    const list = Array.isArray(answers.value[index.value]) ? [...answers.value[index.value]] : []
    const pos = list.indexOf(letter)
    if (pos >= 0) list.splice(pos, 1)
    else list.push(letter)
    list.sort()
    answers.value[index.value] = list
  } else {
    answers.value[index.value] = letter
  }
}

function requireCurrent() {
  if (hasAnswer(answers.value[index.value])) return true
  toast('请先选择答案')
  return false
}

function next() {
  if (requireCurrent()) index.value += 1
}

function prev() {
  index.value -= 1
}

function goTo(i) {
  if (i > index.value && !requireCurrent()) return
  index.value = i
}

function submit() {
  const missing = answers.value.map((_, i) => i).filter((i) => !hasAnswer(answers.value[i]))
  if (missing.length) {
    toast(`还有 ${missing.length} 道题没作答`)
    index.value = missing[0]
    return
  }
  emit('submit', [...answers.value])
}
</script>

<template>
  <div class="quiz">
    <div class="quiz__progress">
      <div class="quiz__progress-top">
        <span>{{ stageLabel }} · <b>{{ index + 1 }}</b>/{{ questions.length }}</span>
        <span class="quiz__type">{{ QUESTION_TYPE_LABEL[current?.type] }}</span>
      </div>
      <div class="quiz__bar">
        <div class="quiz__bar-fill" :style="{ width: `${progress}%` }" />
      </div>
      <div class="quiz__dots">
        <button
          v-for="(q, i) in questions"
          :key="q.id || i"
          type="button"
          class="qdot"
          :class="{
            'is-current': i === index,
            'is-answered': hasAnswer(answers[i]),
          }"
          @click="goTo(i)"
        >
          {{ i + 1 }}
        </button>
      </div>
    </div>

    <div v-if="current" class="quiz__card">
      <h2 class="quiz__stem">{{ current.stem }}</h2>
      <div v-if="current.type !== 'judge'" class="quiz__options">
        <button
          v-for="(opt, i) in current.options"
          :key="i"
          type="button"
          class="opt"
          :class="{ 'is-selected': isSelected(i) }"
          @click="pick(i)"
        >
          <span class="opt__letter">{{ optionLetter(i) }}</span>
          <span class="opt__text">{{ opt }}</span>
          <span class="opt__mark">
            <AppIcon v-if="isSelected(i)" name="check" :size="16" />
          </span>
        </button>
      </div>
      <div v-else class="quiz__judge">
        <button
          v-for="(opt, i) in current.options"
          :key="i"
          type="button"
          class="jopt"
          :class="{ 'is-selected': isSelected(i) }"
          @click="pick(i)"
        >
          <span class="jopt__icon">
            <AppIcon :name="i === 0 ? 'checkCircle' : 'xCircle'" :size="24" />
          </span>
          <span class="jopt__text">{{ opt }}</span>
        </button>
      </div>
      <p class="quiz__hint">
        <AppIcon name="info" :size="14" />
        {{ current.type === 'multiple' ? '多选题，可选择多个选项' : '单项选择' }}
      </p>
    </div>

    <div class="quiz__actions">
      <button v-if="index > 0" type="button" class="btn btn--secondary btn--lg quiz__btn" @click="prev">
        <AppIcon name="chevronLeft" :size="18" />
        上一题
      </button>
      <button
        v-if="index < questions.length - 1"
        type="button"
        class="btn btn--primary btn--lg quiz__btn"
        @click="next"
      >
        下一题
        <AppIcon name="chevronRight" :size="18" />
      </button>
      <button v-else type="button" class="btn btn--primary btn--lg quiz__btn" @click="submit">提交</button>
    </div>
  </div>
</template>

<style scoped>
.quiz {
  padding: var(--sp-4) var(--sp-4) var(--sp-8);
}

.quiz__progress-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--sp-2);
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.quiz__progress-top b {
  color: var(--text-primary);
  font-size: var(--fs-md);
}

.quiz__type {
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

.quiz__bar {
  height: 6px;
  background: var(--bg-muted);
  border-radius: var(--r-full);
  overflow: hidden;
}

.quiz__bar-fill {
  height: 100%;
  background: var(--brand-primary);
  border-radius: var(--r-full);
  transition: width var(--t-normal);
}

.quiz__dots {
  display: flex;
  gap: var(--sp-2);
  margin-top: var(--sp-3);
  flex-wrap: wrap;
}

.qdot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1px solid var(--border);
  background: var(--bg-surface);
  color: var(--text-disabled);
  font-size: var(--fs-xs);
  cursor: pointer;
}

.qdot.is-answered {
  background: var(--success-bg);
  border-color: transparent;
  color: var(--success-text);
  font-weight: var(--fw-semibold);
}

.qdot.is-current {
  border-color: var(--brand-primary);
  color: var(--brand-primary);
  font-weight: var(--fw-semibold);
}

.qdot.is-current.is-answered {
  background: var(--brand-primary);
  color: #fff;
}

.quiz__card {
  margin-top: var(--sp-4);
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  padding: var(--sp-5) var(--sp-4);
  box-shadow: var(--shadow-sm);
}

.quiz__stem {
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  line-height: 1.65;
  margin: 0 0 var(--sp-4);
}

.quiz__options {
  display: flex;
  flex-direction: column;
  gap: var(--sp-3);
}

.opt {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  width: 100%;
  min-height: var(--tap-comfort);
  padding: var(--sp-3) var(--sp-4);
  border: 1.5px solid var(--border);
  border-radius: var(--r-lg);
  background: var(--bg-surface);
  text-align: left;
  cursor: pointer;
}

.opt.is-selected {
  border-color: var(--brand-primary);
  background: var(--brand-soft);
}

.opt__letter {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--bg-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  flex-shrink: 0;
}

.opt.is-selected .opt__letter {
  background: var(--brand-primary);
  color: #fff;
}

.opt__text {
  flex: 1;
  font-size: var(--fs-base);
  line-height: 1.55;
}

.opt__mark {
  width: 20px;
  display: flex;
  justify-content: center;
  color: var(--brand-primary);
}

.quiz__judge {
  display: flex;
  gap: var(--sp-3);
}

.jopt {
  flex: 1;
  min-height: 84px;
  border: 1.5px solid var(--border);
  border-radius: var(--r-lg);
  background: var(--bg-surface);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  cursor: pointer;
}

.jopt.is-selected {
  border-color: var(--brand-primary);
  background: var(--brand-soft);
}

.quiz__hint {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: var(--sp-4);
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

.quiz__actions {
  display: flex;
  gap: var(--sp-3);
  margin-top: var(--sp-5);
}

.quiz__btn {
  flex: 1;
}
</style>
