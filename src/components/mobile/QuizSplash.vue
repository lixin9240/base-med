<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { prepareSpeech, speak } from '@/utils/speech'

const props = defineProps({
  /** 题目已就绪后，进度条才收成「开始答题」 */
  canLeave: { type: Boolean, default: false },
  /** 点击开始时朗读的句子，没有则不播 */
  cue: { type: String, default: '' },
})

const emit = defineEmits(['finished'])

const LOAD_MS = 3200
const EXIT_MS = 480

const root = ref(null)
const startBtn = ref(null)
const playing = ref(true)
const leaving = ref(false)
const progress = ref(0)

let raf = 0
let sequenceTimer = 0
let leaveTimer = 0
const sequenceDone = ref(false)

function armTrace() {
  root.value?.querySelectorAll('.ecg-path, .seal-ring').forEach((shape) => {
    const length = shape.getTotalLength()
    shape.style.strokeDasharray = String(length)
    shape.style.strokeDashoffset = String(length)
  })
}

const canStart = computed(() => sequenceDone.value && props.canLeave)

function beginLeave() {
  if (leaving.value || !canStart.value) return
  leaving.value = true
  leaveTimer = window.setTimeout(() => emit('finished'), EXIT_MS)
}

function onStart() {
  if (props.cue) speak(props.cue)
  beginLeave()
}

watch(canStart, (ready) => {
  if (!ready) return
  nextTick(() => startBtn.value?.focus())
})

watch(
  () => props.cue,
  (cue) => {
    if (cue) prepareSpeech(cue)
  },
  { immediate: true },
)

onMounted(() => {
  armTrace()
  playing.value = true
  const start = performance.now()
  const step = (now) => {
    progress.value = Math.min(100, Math.round(((now - start) / LOAD_MS) * 100))
    if (progress.value < 100) raf = window.requestAnimationFrame(step)
  }
  raf = window.requestAnimationFrame(step)
  sequenceTimer = window.setTimeout(() => {
    progress.value = 100
    sequenceDone.value = true
  }, LOAD_MS)
})

onBeforeUnmount(() => {
  window.cancelAnimationFrame(raf)
  window.clearTimeout(sequenceTimer)
  window.clearTimeout(leaveTimer)
})
</script>

<template>
  <div
    ref="root"
    class="quiz-splash"
    :class="{ 'is-play': playing, 'is-leave': leaving }"
    :aria-hidden="canStart ? 'false' : 'true'"
  >
    <div class="stickers">
      <div class="sticker sticker-card">
        <div class="sticker-bob">
          <svg viewBox="0 0 84 70" role="presentation">
            <rect x="3" y="4" width="78" height="62" rx="14" class="sp-card" stroke-width="1.5" />
            <text x="14" y="22" class="sp-label" font-size="0.6875em" font-family="Microsoft YaHei, sans-serif">单选</text>
            <circle cx="20" cy="44" r="8" class="sp-check-bg" stroke-width="1.6" />
            <path d="M16.4 44.2 L19 46.8 L24 41.2" fill="none" class="sp-check" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M34 38 H70 M34 46 H60" fill="none" class="sp-rule" stroke-width="2.2" stroke-linecap="round" />
          </svg>
        </div>
      </div>
      <div class="sticker sticker-ask">
        <div class="sticker-bob">
          <svg viewBox="0 0 64 60" role="presentation">
            <path d="M8 6 H50 A8 8 0 0 1 58 14 V36 A8 8 0 0 1 50 44 H28 L18 54 V44 H14 A8 8 0 0 1 6 36 V14 A8 8 0 0 1 14 6 Z" class="sp-ask-bubble" stroke-width="1.6" />
            <text x="32" y="32" text-anchor="middle" class="sp-ask-mark" font-size="1.375em" font-family="Songti SC, SimSun, serif">?</text>
          </svg>
        </div>
      </div>
      <div class="sticker sticker-ok">
        <div class="sticker-bob">
          <svg viewBox="0 0 58 58" role="presentation">
            <circle cx="29" cy="29" r="24" class="sp-ok-outer" />
            <circle cx="29" cy="29" r="18" class="sp-ok-inner" />
            <path d="M18 30.5 L25.2 37.2 L40 21.5" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
      </div>
      <div class="sticker sticker-pill">
        <div class="sticker-bob">
          <svg viewBox="0 0 78 40" role="presentation">
            <rect x="2" y="4" width="74" height="32" rx="16" class="sp-pill-body" stroke-width="1.4" />
            <rect x="2" y="4" width="36" height="32" rx="16" class="sp-pill-half" />
            <path d="M39 10 V30" class="sp-pill-line" stroke-width="1.4" />
            <circle cx="20" cy="20" r="4" fill="#ffffff" />
          </svg>
        </div>
      </div>
    </div>

    <div class="splash-center">
      <svg class="ecg" viewBox="0 0 360 64" role="presentation">
        <path class="ecg-path" d="M0 36 H148 L160 36 L168 30 L176 42 L186 18 L196 54 L206 36 H360" />
      </svg>
      <h1 class="splash-title"><span>医</span><span>疗</span><span>答</span><span>题</span></h1>
      <div class="seal">
        <svg viewBox="0 0 80 80" role="presentation">
          <circle class="seal-ring" cx="40" cy="40" r="30" />
        </svg>
        <span class="seal-char">答</span>
      </div>
      <p class="splash-sub">每日一题 · 开始作答</p>
    </div>

    <div class="load" :class="{ 'is-ready': canStart }">
      <button
        ref="startBtn"
        type="button"
        class="load-track"
        :disabled="!canStart || leaving"
        @click="onStart"
      >
        <span class="load-fill"></span>
        <span class="start-text">开始答题</span>
      </button>
      <p class="load-label" :aria-hidden="canStart ? 'true' : 'false'">
        正在载入题目 <span class="load-num">{{ progress }}</span>%
      </p>
    </div>
  </div>
</template>

<style scoped>
.quiz-splash {
  --serif: 'Songti SC', 'STSong', 'SimSun', 'Noto Serif SC', serif;
  --load-ms: 3.2s;
  position: fixed;
  top: 0;
  bottom: 0;
  /* 只盖住手机端这一列，左右留出的页面背景保持不动 */
  left: 0;
  right: 0;
  width: min(100%, var(--mobile-max, 480px));
  margin-inline: auto;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background:
    radial-gradient(75% 42% at 50% 0%, var(--brand-soft) 0%, transparent 70%),
    radial-gradient(60% 36% at 88% 100%, var(--brand-tint) 0%, transparent 62%),
    var(--bg-page-gradient);
  color: var(--brand-primary-active);
}

.splash-center {
  position: relative;
  z-index: 2;
  width: min(480px, 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 18px 0 76px;
}

.ecg {
  width: calc(100% - 28px);
  height: 52px;
  display: block;
}

.ecg-path {
  fill: none;
  stroke: var(--brand-primary);
  stroke-width: 2.4;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 520;
  stroke-dashoffset: 520;
  filter: drop-shadow(0 0 4px var(--brand-glow));
}

.seal {
  position: relative;
  width: 68px;
  height: 68px;
  margin-top: 12px;
}

.seal svg {
  width: 100%;
  height: 100%;
  display: block;
}

.seal-ring {
  fill: none;
  stroke: var(--brand-primary);
  stroke-width: 1.35;
  stroke-dasharray: 190;
  stroke-dashoffset: 190;
}

.seal-char {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-family: var(--serif);
  font-size: 1.75em;
  letter-spacing: 0;
  opacity: 0;
}

.splash-title {
  margin: 6px 0 0;
  display: flex;
  gap: 0.16em;
  font-family: var(--serif);
  font-size: 2.5em;
  font-weight: 600;
  letter-spacing: 0;
}

.splash-title span {
  display: block;
  opacity: 0;
  transform-origin: 50% 70%;
}

.splash-sub {
  margin: 12px 0 0;
  color: var(--text-secondary);
  font-size: 0.8125em;
  letter-spacing: 0.28em;
  text-indent: 0.28em;
  opacity: 0;
}

.load {
  position: absolute;
  left: 50%;
  bottom: 32px;
  z-index: 3;
  width: min(400px, calc(100% - 80px));
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.load-track {
  position: relative;
  display: grid;
  place-items: center;
  width: 100%;
  height: 6px;
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: var(--brand-soft);
  color: #fff;
  font: inherit;
  overflow: hidden;
  cursor: default;
  transition:
    width 0.32s cubic-bezier(0.76, 0, 0.18, 1),
    height 0.32s cubic-bezier(0.76, 0, 0.18, 1),
    box-shadow 0.32s ease;
}

.is-ready .load-track {
  width: 196px;
  height: 48px;
  cursor: pointer;
  box-shadow: 0 8px 18px var(--brand-glow);
}

.load-track:disabled {
  opacity: 1;
}

.load-track:focus-visible {
  outline: 2px solid var(--brand-primary);
  outline-offset: 3px;
}

.load-fill {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  display: block;
  width: 0;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--brand-primary-hover), var(--brand-primary));
}

.start-text {
  position: relative;
  z-index: 1;
  opacity: 0;
  font-size: 1em;
  font-weight: 600;
  letter-spacing: 0.14em;
}

.is-ready .start-text {
  opacity: 1;
  transition: opacity 0.2s ease 0.12s;
}

.load-label {
  margin: 0;
  max-height: 20px;
  overflow: hidden;
  text-align: center;
  color: var(--brand-primary);
  font-size: 0.75em;
  letter-spacing: 0.16em;
  transition:
    opacity 0.16s ease,
    max-height 0.28s ease;
}

.is-ready .load-label {
  opacity: 0;
  max-height: 0;
}

.stickers {
  pointer-events: none;
}

.sticker {
  position: absolute;
  z-index: 1;
  opacity: 0;
  filter: drop-shadow(0 8px 12px var(--shadow-color2-md));
}

.sticker svg {
  display: block;
  width: 100%;
  height: auto;
}

/* 贴纸插画跟随主题色 */
.sp-card {
  fill: var(--bg-surface-solid);
  stroke: var(--accent-teal-soft);
}

.sp-label {
  fill: var(--brand-primary);
}

.sp-check-bg {
  fill: var(--brand-tint);
  stroke: var(--brand-primary);
}

.sp-check {
  stroke: var(--brand-primary);
}

.sp-rule {
  stroke: var(--border);
}

.sp-ask-bubble {
  fill: var(--bg-surface-solid);
  stroke: var(--brand-primary);
}

.sp-ask-mark {
  fill: var(--brand-primary);
}

.sp-ok-outer {
  fill: var(--brand-primary);
}

.sp-ok-inner {
  fill: var(--brand-primary-hover);
}

.sp-pill-body {
  fill: var(--bg-surface-solid);
  stroke: var(--accent-teal-soft);
}

.sp-pill-half {
  fill: var(--brand-primary);
}

.sp-pill-line {
  stroke: var(--border);
}

.sticker-card { top: 36%; left: max(8px, calc(50% - 232px)); width: 70px; }
.sticker-ask { top: 22%; right: max(10px, calc(50% - 230px)); width: 56px; }
.sticker-ok { top: 52%; right: max(16px, calc(50% - 224px)); width: 54px; }
.sticker-pill { top: 58%; left: max(18px, calc(50% - 222px)); width: 68px; }

.is-play .ecg-path { animation: draw 1.2s cubic-bezier(0.45, 0, 0.2, 1) 0.18s forwards; }
.is-play .seal-ring { animation: draw 0.6s cubic-bezier(0.4, 0, 0.15, 1) 1.2s forwards; }
.is-play .seal-char { animation: squash 0.35s cubic-bezier(0.2, 0.8, 0.2, 1) 1.58s forwards; }
.is-play .splash-title span:nth-child(1) { animation: warp-a 0.41s cubic-bezier(0.18, 0.8, 0.2, 1) 1.78s forwards; }
.is-play .splash-title span:nth-child(2) { animation: warp-b 0.41s cubic-bezier(0.18, 0.8, 0.2, 1) 1.85s forwards; }
.is-play .splash-title span:nth-child(3) { animation: warp-c 0.41s cubic-bezier(0.18, 0.8, 0.2, 1) 1.93s forwards; }
.is-play .splash-title span:nth-child(4) { animation: warp-d 0.41s cubic-bezier(0.18, 0.8, 0.2, 1) 2s forwards; }
.is-play .splash-sub { animation: skew-in 0.35s cubic-bezier(0.2, 0.7, 0.2, 1) 2.23s forwards; }
.is-play .sticker-card { animation: pop-left 0.4s cubic-bezier(0.2, 0.85, 0.2, 1) 0.23s forwards; }
.is-play .sticker-ask { animation: pop-right 0.4s cubic-bezier(0.2, 0.85, 0.2, 1) 0.35s forwards; }
.is-play .sticker-ok { animation: pop-right 0.38s cubic-bezier(0.2, 0.85, 0.2, 1) 0.53s forwards; }
.is-play .sticker-pill { animation: pop-left 0.38s cubic-bezier(0.2, 0.85, 0.2, 1) 0.63s forwards; }
.is-play .sticker-bob { animation: bob 2.2s ease-in-out 1.1s infinite; }
.is-play .load-fill { animation: load var(--load-ms) linear forwards; }

.is-leave {
  animation: curtain 0.48s cubic-bezier(0.76, 0, 0.18, 1) forwards;
}

@keyframes draw { to { stroke-dashoffset: 0; } }

@keyframes squash {
  0% { opacity: 0; transform: scaleX(1.7) scaleY(0.35) rotate(-10deg); }
  55% { opacity: 1; transform: scaleX(0.84) scaleY(1.22) rotate(4deg); }
  100% { opacity: 1; transform: none; }
}

@keyframes warp-a {
  0% { opacity: 0; transform: translateY(22px) scaleX(1.55) scaleY(0.45) skewX(-16deg) rotate(-8deg); }
  62% { opacity: 1; transform: translateY(-3px) scaleX(0.9) scaleY(1.14) skewX(6deg) rotate(2deg); }
  100% { opacity: 1; transform: none; }
}

@keyframes warp-b {
  0% { opacity: 0; transform: translateY(-16px) scaleX(0.45) scaleY(1.6) skewY(12deg) rotate(8deg); }
  62% { opacity: 1; transform: translateY(2px) scaleX(1.12) scaleY(0.88) skewY(-4deg) rotate(-2deg); }
  100% { opacity: 1; transform: none; }
}

@keyframes warp-c {
  0% { opacity: 0; transform: translateX(-12px) scale(0.4) rotate(-18deg) skewX(18deg); }
  60% { opacity: 1; transform: scale(1.12) rotate(5deg) skewX(-6deg); }
  100% { opacity: 1; transform: none; }
}

@keyframes warp-d {
  0% { opacity: 0; transform: translateY(14px) scaleX(1.8) scaleY(0.4) rotate(12deg); }
  64% { opacity: 1; transform: scaleX(0.88) scaleY(1.16) rotate(-3deg); }
  100% { opacity: 1; transform: none; }
}

@keyframes skew-in {
  from { opacity: 0; transform: skewX(-18deg) scaleX(1.25); letter-spacing: 0.55em; }
  to { opacity: 1; transform: none; letter-spacing: 0.28em; }
}

@keyframes pop-left {
  0% { opacity: 0; transform: translate(-16px, 12px) rotate(-24deg) scale(0.55); }
  100% { opacity: 1; transform: rotate(-11deg) scale(1); }
}

@keyframes pop-right {
  0% { opacity: 0; transform: translate(16px, 10px) rotate(22deg) scale(0.55); }
  100% { opacity: 1; transform: rotate(9deg) scale(1); }
}

@keyframes bob {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-7px); }
}

@keyframes curtain {
  from { transform: translateY(0); }
  to { transform: translateY(-101%); }
}

@keyframes load { to { width: 100%; } }
</style>
