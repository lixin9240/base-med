<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import { formatArticleHtml } from '@/utils/article'
import { speak, whenSpeechEnds } from '@/utils/speech'
import { toast } from '@/utils/toast'
import { getPublicVideoDetail, reportVideoProgress } from '@/api/public'
import { currentDrId, issueDrId, setSkipWatch } from '@/store/mobile/session'

const route = useRoute()
const router = useRouter()
const videoId = String(route.query.videoId || '')
// 分享链接入口（后端生成的 H5 链接形如 /mobile/learn?video_id=5&share=xxx）
const shareVideoId = String(route.query.video_id || '')

const video = ref(null)
const error = ref('')
const videoEl = ref(null)
const watched = ref(0) // 观看进度百分比
const required = ref(90) // 解锁门槛
const canQuiz = ref(false) // 是否已解锁后测
const badRate = ref(false)
const noVideoMode = ref(false)
const loading = ref(true)

// TODO: 后端暂未返回答题/中奖人数统计，先使用占位数据
const answeredPeople = 1286
const winnerPeople = 357

let maxTime = 0 // 最大观看位置（秒）
let reportTimer = null

const articleHtml = computed(() => formatArticleHtml(video.value?.articleBody || ''))
const DIAL_CIRCUMFERENCE = 2 * Math.PI * 50
const ringPercent = computed(() => {
  if (noVideoMode.value) return 100
  return Math.min(100, Math.max(0, Math.round(watched.value)))
})
const ringOffset = computed(() => DIAL_CIRCUMFERENCE * (1 - ringPercent.value / 100))
const isCinema = computed(() => Boolean(video.value?.videoUrl) && !noVideoMode.value)
const paused = ref(true)
const playhead = ref(0)
const showScrub = ref(false)
const volume = ref(1)
const showVolume = ref(false)
const scrubbing = ref(false)
const stageEl = ref(null)
const isLandscapeSource = ref(false)
const wideMode = ref(false)
const deviceLandscape = ref(false)
const videoRatio = ref('16 / 9')
const pointsEl = ref(null)
const pointsOverflow = ref(false)
const pointsOpen = ref(false)
let lastSample = 0
let pointsObserver = null
let scrubHideTimer = null
let orientMedia = null
let stagePointer = null
let suppressClick = false
let volumeGesture = null
let volumeHideTimer = null

function measurePoints() {
  const el = pointsEl.value
  if (!el || pointsOpen.value) return
  pointsOverflow.value = el.scrollHeight > el.clientHeight + 2
}

function openPoints() {
  pointsOpen.value = true
  pointsOverflow.value = false
}

watch([articleHtml, isCinema], () => {
  pointsOpen.value = false
  pointsOverflow.value = false
  nextTick(() => {
    requestAnimationFrame(() => {
      measurePoints()
      const el = pointsEl.value
      pointsObserver?.disconnect()
      if (!el) return
      pointsObserver = new ResizeObserver(() => measurePoints())
      pointsObserver.observe(el)
    })
  })
})

function onVideoClick() {
  if (suppressClick) {
    suppressClick = false
    return
  }
  togglePlay()
}

function togglePlay() {
  const el = videoEl.value
  if (!el) return
  if (el.paused) {
    el.play().catch(() => {
      toast('浏览器已限制自动播放，请点击播放按钮手动播放')
    })
  } else {
    el.pause()
  }
}

function onPlay() {
  paused.value = false
}

function onPause() {
  paused.value = true
}

function syncDeviceLandscape() {
  const width = window.innerWidth
  const height = window.innerHeight
  deviceLandscape.value = width > height
  const root = document.documentElement
  root.style.setProperty('--stage-long', `${Math.max(width, height)}px`)
  root.style.setProperty('--stage-short', `${Math.min(width, height)}px`)
}

function onLoadedMeta() {
  const el = videoEl.value
  if (!el?.videoWidth || !el?.videoHeight) return
  videoRatio.value = `${el.videoWidth} / ${el.videoHeight}`
  isLandscapeSource.value = el.videoWidth > el.videoHeight
}

function toggleWide() {
  syncDeviceLandscape()
  wideMode.value = !wideMode.value
}

onMounted(async () => {
  try {
    // 通过分享链接进入：先签发匿名编号，再跳转到对应视频的前测题页面
    if (shareVideoId) {
      await issueDrId(shareVideoId)
      router.replace({ name: 'mobile-quiz', query: { videoId: shareVideoId, stage: 'pre' } })
      return
    }

    const drId = currentDrId()
    if (!drId || !videoId) {
      router.replace('/mobile/home')
      return
    }
    const detail = await getPublicVideoDetail(drId, videoId)
    video.value = {
      id: detail.id,
      title: detail.title,
      category: detail.category_name,
      cover: detail.cover_url,
      speaker: detail.speaker,
      speakerTitle: detail.speaker_title,
      speakerOrg: detail.speaker_org,
      intro: detail.brief_intro,
      videoUrl: detail.video_url,
      articleBody: detail.brief_intro
,
      examState: detail.exam_state,
    }

    const wp = detail.watch_progress || {}
    if (!detail.video_url) {
      // 纯图文内容：无需观看进度，可直接答题
      noVideoMode.value = true
      canQuiz.value = true
    } else {
      watched.value = wp.progress_percent ?? 0
      required.value = wp.watch_required_percent ?? 90
      canQuiz.value = wp.is_unlocked === 1
      maxTime = Number(wp.watched_seconds) || 0
      reportTimer = window.setInterval(reportProgress, 3000)
      loading.value = false
      const speechDone = whenSpeechEnds()
      await nextTick()
      await speechDone
      await tryAutoPlay()
    }
    document.addEventListener('visibilitychange', onVisibility)
    syncDeviceLandscape()
    window.addEventListener('resize', syncDeviceLandscape)
    orientMedia = window.matchMedia('(orientation: landscape)')
    orientMedia.addEventListener('change', syncDeviceLandscape)
  } catch (e) {
    error.value = e?.message || '加载失败'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  clearInterval(reportTimer)
  clearTimeout(scrubHideTimer)
  document.removeEventListener('visibilitychange', onVisibility)
  window.removeEventListener('resize', syncDeviceLandscape)
  orientMedia?.removeEventListener('change', syncDeviceLandscape)
  pointsObserver?.disconnect()
  clearTimeout(volumeHideTimer)
  reportProgress()
})

async function reportProgress() {
  const drId = currentDrId()
  const el = videoEl.value
  if (!drId || !videoId || !el) return
  try {
    const res = await reportVideoProgress({
      dr_id: drId,
      video_id: videoId,
      watched_seconds: maxTime || el.currentTime || 0,
      duration: Math.round(el.duration) || undefined,
    })
    if (res) {
      watched.value = Math.max(watched.value, res.progress_percent ?? 0)
      required.value = res.watch_required_percent ?? required.value
      if (res.is_unlocked === 1 && !canQuiz.value) {
        canQuiz.value = true
        toast('已看完，可以去答题了')
      }
    }
  } catch {
    // 上报失败忽略，避免打断播放
  }
}

function onVisibility() {
  if (document.visibilityState === 'hidden') reportProgress()
}

function syncProgress() {
  const el = videoEl.value
  if (!el || !el.duration) return
  const percent = Math.min(100, Math.round((maxTime / el.duration) * 100))
  if (percent > watched.value) watched.value = percent
  if (percent >= required.value && !canQuiz.value) {
    canQuiz.value = true
    toast('已看完，可以去答题了')
  }
}

function onTimeUpdate() {
  const el = videoEl.value
  if (!el || !el.duration) return
  if (!scrubbing.value) playhead.value = Math.min(100, (el.currentTime / el.duration) * 100)
  const playing =
    !el.paused && !el.seeking && !scrubbing.value && Number(el.playbackRate) === 1 && document.visibilityState === 'visible'
  if (playing) {
    const delta = el.currentTime - lastSample
    if (delta > 0 && delta < 1.2) maxTime += delta
  }
  lastSample = el.currentTime
  syncProgress()
}

function applyVolumeFromPointer(event) {
  const el = videoEl.value
  const stage = stageEl.value
  if (!el || !stage || !stagePointer) return
  const span = Math.max(160, stage.getBoundingClientRect().height)
  const next = Math.min(1, Math.max(0, stagePointer.volume + (stagePointer.y - event.clientY) / span))
  volume.value = next
  el.volume = next
  el.muted = next === 0
}

function onStagePointerDown(event) {
  if (!isCinema.value) return
  if (event.target.closest('button, a, .stage-copy')) return
  volumeGesture = null
  stagePointer = {
    x: event.clientX,
    y: event.clientY,
    id: event.pointerId,
    volume: volume.value,
  }
}

function onStagePointerMove(event) {
  if (!stagePointer || event.pointerId !== stagePointer.id) return
  const dx = event.clientX - stagePointer.x
  const dy = event.clientY - stagePointer.y
  if (!volumeGesture) {
    if (Math.hypot(dx, dy) < 8) return
    volumeGesture = Math.abs(dy) > Math.abs(dx) ? 'volume' : 'skip'
    if (volumeGesture !== 'volume') return
    showVolume.value = true
    suppressClick = true
    clearTimeout(volumeHideTimer)
    try {
      stageEl.value?.setPointerCapture?.(event.pointerId)
    } catch {
      // 指针已被释放时忽略
    }
  }
  if (volumeGesture !== 'volume') return
  applyVolumeFromPointer(event)
}

function onStagePointerUp(event) {
  if (stagePointer && event.pointerId !== stagePointer.id) return
  const wasVolume = volumeGesture === 'volume'
  stagePointer = null
  volumeGesture = null
  if (!wasVolume) return
  volumeHideTimer = window.setTimeout(() => {
    showVolume.value = false
  }, 500)
}

function onQuizTap() {
  if (!canQuiz.value) {
    toast(`请看完视频（${required.value}% 后可答题）`)
    return
  }
  goQuiz()
}

function onRateChange() {
  const el = videoEl.value
  if (el) badRate.value = Number(el.playbackRate) !== 1
}

function onEnded() {
  const el = videoEl.value
  if (el?.duration) {
    maxTime = el.duration
    syncProgress()
    reportProgress()
  }
}

// 自动播放；若被浏览器自动播放策略拦截，则提示用户手动播放
async function tryAutoPlay() {
  const el = videoEl.value
  if (!el) return
  onLoadedMeta()
  try {
    await el.play()
  } catch {
    toast('浏览器已限制自动播放，请点击播放按钮手动播放')
  }
}

function onVideoError() {
  noVideoMode.value = true
  canQuiz.value = true
  setSkipWatch(videoId)
}

async function goQuiz() {
  // 跳转前先把进度同步到后端，确保后端 is_unlocked 已更新（否则长视频到 90% 点去答题会被后测门槛弹回）
  await reportProgress()
  const title = video.value?.title
  speak(title ? `视频已看完，开始《${title}》的看完答题` : '视频已看完，开始看完答题')
  router.push({ name: 'mobile-quiz', query: { videoId, stage: 'post' } })
}
</script>

<template>
  <div
    ref="stageEl"
    class="learn"
    :class="{
      'is-cinema': isCinema,
      'is-letterbox': isCinema && isLandscapeSource && !wideMode,
      'is-wide': isCinema && wideMode,
      'is-turned': isCinema && wideMode && !deviceLandscape,
    }"
    @pointerdown="onStagePointerDown"
    @pointermove="onStagePointerMove"
    @pointerup="onStagePointerUp"
    @pointercancel="onStagePointerUp"
  >
    <PageNav
      title="观看视频"
      :show-back="false"
      :overlay="isCinema"
      :answered-people="answeredPeople"
      :winner-people="winnerPeople"
    />

    <div v-if="error" class="empty">
      <div class="empty__icon">
        <AppIcon name="alert" :size="44" />
      </div>
      <p class="empty__text">{{ error }}</p>
      <div class="empty__actions">
        <button type="button" class="btn btn--secondary btn--md" @click="router.replace('/mobile/home')">
          返回列表
        </button>
      </div>
    </div>

    <div v-else-if="loading" class="empty">
      <div class="loading-spinner" role="status" aria-label="加载中" />
      <p class="empty__text">加载中…</p>
    </div>

    <div v-else-if="!video" class="empty">
      <div class="empty__icon">
        <AppIcon name="alert" :size="44" />
      </div>
      <p class="empty__text">视频不存在或已下架</p>
      <div class="empty__actions">
        <button type="button" class="btn btn--secondary btn--md" @click="router.replace('/mobile/home')">
          返回列表
        </button>
      </div>
    </div>

    <template v-else>
      <div v-if="isCinema" class="player" :style="{ '--video-ratio': videoRatio }">
        <video
          ref="videoEl"
          class="player__el"
          :src="video.videoUrl"
          :poster="video.cover || undefined"
          playsinline
          webkit-playsinline
          x5-playsinline
          x5-video-player-type="h5-page"
          x5-video-player-fullscreen="false"
          preload="auto"
          :aria-label="`播放视频：${video.title}`"
          @loadedmetadata="onLoadedMeta"
          @click="onVideoClick"
          @play="onPlay"
          @pause="onPause"
          @timeupdate="onTimeUpdate"
          @ratechange="onRateChange"
          @ended="onEnded"
          @error="onVideoError"
        />
        <div class="player__veil" aria-hidden="true" />
        <button v-if="paused" type="button" class="player__play" aria-label="播放" @click.stop="togglePlay">
          <span class="player__play-icon" />
        </button>
        <button
          type="button"
          class="orient"
          :aria-label="wideMode ? '切换竖屏' : '切换横屏'"
          @click.stop="toggleWide"
        >
          <AppIcon name="landscape" :size="20" />
        </button>
        <div v-if="showVolume" class="volume-tip">音量 {{ Math.round(volume * 100) }}%</div>
        <div v-if="badRate" class="player__tip">
          <AppIcon name="alert" :size="14" />
          倍速播放不计入观看进度
        </div>
      </div>
      <div v-else class="player-fallback">
        <AppIcon name="alert" :size="26" />
        <b>{{ video.videoUrl ? '视频暂时无法播放' : '本条内容暂无视频' }}</b>
        <span>可直接阅读下方的文字说明，读完后同样可以答题。</span>
      </div>

      <section v-if="!isCinema" class="info">
        <h1 class="info__title">{{ video.title }}</h1>
        <p class="info__meta">
          <span class="badge badge--info">{{ video.category }}</span>
          <span>
            {{ video.speaker }}
            <template v-if="video.speakerTitle"> · {{ video.speakerTitle }}</template>
          </span>
        </p>
        <p class="info__intro">{{ video.intro }}</p>
        <p v-if="video.speakerOrg" class="info__speaker-org">{{ video.speakerOrg }}</p>
      </section>

      <section v-if="!isCinema && video.articleBody" class="article">
        <h2 class="article__title">视频要点</h2>
        <div class="article__body" v-html="articleHtml" />
      </section>

      <div class="dock">
        <template v-if="isCinema">
          <div class="dock__row">
            <aside class="stage-copy" :class="{ 'is-open': pointsOpen }">
              <h1 class="stage-copy__title">{{ video.title }}</h1>
              <div v-if="video.articleBody" class="stage-copy__fold">
                <div
                  ref="pointsEl"
                  class="stage-copy__points"
                  :class="{ 'is-clamp': !pointsOpen }"
                  v-html="articleHtml"
                />
                <button v-if="pointsOverflow && !pointsOpen" type="button" class="stage-copy__more" @click.stop="openPoints">
                  查看更多
                </button>
              </div>
            </aside>
            <div
              class="quiz-dial quiz-dial--overlay dock__dial"
              :class="{ 'is-ready': canQuiz, 'is-wait': !canQuiz }"
            >
              <svg class="quiz-dial__ring" viewBox="0 0 120 120" aria-hidden="true">
                <circle class="quiz-dial__track" cx="60" cy="60" r="50" />
                <circle
                  class="quiz-dial__progress"
                  cx="60"
                  cy="60"
                  r="50"
                  :stroke-dasharray="DIAL_CIRCUMFERENCE"
                  :stroke-dashoffset="ringOffset"
                />
              </svg>
              <button type="button" class="quiz-dial__btn" :aria-disabled="!canQuiz" @click="onQuizTap">
                <span class="quiz-dial__label">去答题</span>
                <span class="quiz-dial__sub">{{ canQuiz ? '已解锁' : `${ringPercent}%` }}</span>
              </button>
            </div>
          </div>
          <p class="dock__note">该视频为科普视频，仅供健康学习，不构成诊疗建议。</p>
          <div class="scrub" :class="{ 'is-on': showScrub }">
            <div class="scrub__line">
              <div class="scrub__fill" :style="{ width: `${playhead}%` }" />
            </div>
          </div>
        </template>
        <div v-else class="learn__footer">
          <div class="learn__dock">
            <div class="quiz-dial" :class="{ 'is-ready': canQuiz, 'is-wait': !canQuiz }">
              <svg class="quiz-dial__ring" viewBox="0 0 120 120" aria-hidden="true">
                <circle class="quiz-dial__track" cx="60" cy="60" r="50" />
                <circle
                  class="quiz-dial__progress"
                  cx="60"
                  cy="60"
                  r="50"
                  :stroke-dasharray="DIAL_CIRCUMFERENCE"
                  :stroke-dashoffset="ringOffset"
                />
              </svg>
              <button type="button" class="quiz-dial__btn" :aria-disabled="!canQuiz" @click="onQuizTap">
                <span class="quiz-dial__label">去答题</span>
                <span class="quiz-dial__sub">{{ canQuiz ? '已解锁' : `${ringPercent}%` }}</span>
              </button>
            </div>
            <p class="learn__hint">
              {{ canQuiz ? '已解锁，可以答题' : `已观看 ${ringPercent}%，看完 ${required}% 后可答题` }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.learn {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg-page);
  padding-bottom: calc(210px + env(safe-area-inset-bottom, 0px));
  margin: calc(-1 * var(--sp-4));
  margin-top: calc(-1 * var(--sp-4));
  margin-left: calc(-1 * max(var(--sp-4), env(safe-area-inset-left, 0px)));
  margin-right: calc(-1 * max(var(--sp-4), env(safe-area-inset-right, 0px)));
}

/* 加载中的旋转缓冲圈 */
.loading-spinner {
  width: 32px;
  height: 32px;
  margin: 0 auto var(--sp-3);
  border: 3px solid var(--border);
  border-top-color: var(--brand-primary);
  border-radius: 50%;
  animation: loading-spin 0.8s linear infinite;
}

@keyframes loading-spin {
  to {
    transform: rotate(360deg);
  }
}

.player {
  position: absolute;
  inset: 0;
  z-index: 0;
  background: #000;
  overflow: hidden;
}

.player__el {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  background: #000;
}

/* 横屏片源先居中按原比例显示，不裁成竖屏 */
.learn.is-letterbox .player__el {
  position: absolute;
  top: 50%;
  left: 0;
  width: 100%;
  height: auto;
  max-height: 56%;
  aspect-ratio: var(--video-ratio, 16 / 9);
  object-fit: contain;
  transform: translateY(-50%);
}

.learn.is-letterbox .player__play {
  top: 50%;
}

.orient {
  position: absolute;
  top: 50%;
  right: max(10px, env(safe-area-inset-right, 0px));
  z-index: 4;
  width: 40px;
  height: 40px;
  margin-top: -20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 50%;
  background: rgb(0 0 0 / 0.45);
  color: #fff;
  cursor: pointer;
}

/* 上下渐变只负责衬字，不挡住点击；点击仍落在视频上 */
.player__veil {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background:
    linear-gradient(to bottom, rgb(0 0 0 / 0.5) 0%, transparent 18%),
    linear-gradient(to top, rgb(0 0 0 / 0.78) 0%, rgb(0 0 0 / 0.28) 34%, transparent 58%);
}

.player__play {
  position: absolute;
  left: 50%;
  top: 42%;
  z-index: 2;
  transform: translate(-50%, -50%);
  width: 68px;
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 50%;
  background: rgb(0 0 0 / 0.38);
  cursor: pointer;
}

.player__play-icon {
  width: 0;
  height: 0;
  margin-left: 4px;
  border-style: solid;
  border-width: 13px 0 13px 22px;
  border-color: transparent transparent transparent #fff;
}

.player__tip {
  position: absolute;
  left: var(--sp-3);
  top: calc(56px + env(safe-area-inset-top, 0px));
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--warning-bg);
  color: var(--warning-text);
  font-size: var(--fs-xs);
  padding: 4px 10px;
  border-radius: var(--r-full);
}

/* 竖屏全屏：铺满手机栏，文案层叠在画面前面 */
.learn.is-cinema {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 50%;
  z-index: 10;
  width: 100%;
  max-width: var(--mobile-max);
  margin: 0;
  padding: 0;
  overflow: hidden;
  background: #000;
  transform: translateX(-50%);
  touch-action: none;
}

.learn.is-cinema.is-wide {
  max-width: none;
  width: 100%;
  left: 0;
  transform: none;
}

.learn.is-cinema.is-wide .player__el {
  object-fit: contain;
}

/* 手机仍是竖握时，把整页转成横屏铺满 */
.learn.is-cinema.is-turned {
  width: var(--stage-long, 100dvh);
  height: var(--stage-short, 100vw);
  min-height: 0;
  max-width: none;
  top: 50%;
  bottom: auto;
  left: 50%;
  transform: translate(-50%, -50%) rotate(90deg);
}

.dock {
  display: contents;
}

.learn.is-cinema .dock {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  pointer-events: none;
}

.dock__row {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  min-height: calc(104px + 4px);
}

.stage-copy {
  flex: 1;
  min-width: 0;
  max-width: min(72%, calc(100% - 122px));
  max-height: 28dvh;
  margin: 0 0 0 max(12px, env(safe-area-inset-left, 0px));
  overflow: auto;
  text-align: left;
  pointer-events: auto;
  touch-action: pan-y;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.stage-copy.is-open {
  max-height: 56dvh;
}

.stage-copy::-webkit-scrollbar {
  display: none;
}

.quiz-dial {
  --dial-size: 112px;
  --dial-track: var(--border);
  --dial-progress: var(--brand-primary);
  --dial-glow: var(--brand-glow);
  --dial-text: var(--brand-primary);
  --dial-sub: var(--text-secondary);
  position: relative;
  width: var(--dial-size);
  height: var(--dial-size);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

/* 浮在视频上：透明按钮 + 白色光圈 */
.quiz-dial--overlay {
  --dial-track: rgb(255 255 255 / 0.24);
  --dial-progress: #fff;
  --dial-glow: rgb(255 255 255 / 0.5);
  --dial-text: #fff;
  --dial-sub: rgb(255 255 255 / 0.82);
}

.quiz-dial__ring {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.quiz-dial__track {
  fill: none;
  stroke: var(--dial-track);
  stroke-width: 6;
}

.quiz-dial__progress {
  fill: none;
  stroke: var(--dial-progress);
  stroke-width: 6;
  stroke-linecap: round;
  transition: stroke-dashoffset 0.35s linear;
  filter: drop-shadow(0 0 6px var(--dial-glow));
}

.quiz-dial__btn {
  position: relative;
  z-index: 1;
  width: calc(var(--dial-size) - 24px);
  height: calc(var(--dial-size) - 24px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: var(--dial-text);
  font: inherit;
  cursor: pointer;
}

.quiz-dial__label {
  font-size: var(--fs-base);
  font-weight: var(--fw-bold);
  line-height: 1.2;
}

.quiz-dial__sub {
  font-size: 10px;
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
  color: var(--dial-sub);
}

.quiz-dial--overlay .quiz-dial__label {
  text-shadow: 0 1px 3px rgb(0 0 0 / 0.65);
}

.quiz-dial--overlay .quiz-dial__sub {
  text-shadow: 0 1px 2px rgb(0 0 0 / 0.65);
}

.quiz-dial.is-wait {
  opacity: 0.78;
}

.quiz-dial.is-ready .quiz-dial__progress {
  animation: dial-pulse 1.8s ease-in-out infinite;
}

@keyframes dial-pulse {
  0%,
  100% {
    opacity: 0.7;
  }
  50% {
    opacity: 1;
  }
}

.dock__dial {
  --dial-size: 104px;
  position: absolute;
  right: 0;
  bottom: 0;
  margin: 0 max(10px, env(safe-area-inset-right, 0px)) 4px 0;
  pointer-events: auto;
}

.dock__note {
  margin: 2px max(12px, env(safe-area-inset-right, 0px)) 0 max(12px, env(safe-area-inset-left, 0px));
  font-size: 11px;
  line-height: 1.4;
  color: rgb(255 255 255 / 0.72);
  text-shadow: 0 1px 2px rgb(0 0 0 / 0.65);
  pointer-events: none;
}

.volume-tip {
  position: absolute;
  left: 50%;
  top: 46%;
  z-index: 4;
  transform: translate(-50%, -50%);
  padding: 8px 14px;
  border-radius: 999px;
  background: rgb(0 0 0 / 0.55);
  color: #fff;
  font-size: 13px;
  letter-spacing: 0.04em;
  pointer-events: none;
}

.scrub {
  height: calc(8px + env(safe-area-inset-bottom, 0px));
  display: flex;
  align-items: flex-end;
  pointer-events: none;
}

.scrub__line {
  width: 100%;
  height: 3px;
  background: rgb(255 255 255 / 0.28);
  opacity: 0;
}

.scrub.is-on .scrub__line {
  opacity: 1;
}

.scrub__fill {
  height: 100%;
  background: #fff;
}

.stage-copy__title {
  margin: 0;
  font-size: 13px;
  font-weight: var(--fw-semibold);
  line-height: 1.35;
  color: #fff;
  text-shadow: 0 1px 3px rgb(0 0 0 / 0.7);
}

.stage-copy__fold {
  margin-top: 6px;
}

.stage-copy__points {
  font-size: 11px;
  line-height: 1.45;
}

.stage-copy__points.is-clamp {
  max-height: calc(1.45em * 2);
  overflow: hidden;
}

.stage-copy__points :deep(p),
.stage-copy__points :deep(.art__h),
.stage-copy__points :deep(.art__li) {
  margin: 0 0 3px;
  padding: 0;
  border: none;
  font-size: 11px;
  font-weight: 400;
  line-height: 1.45;
  color: rgb(255 255 255 / 0.92);
  text-shadow: 0 1px 2px rgb(0 0 0 / 0.65);
}

.stage-copy__points.is-clamp :deep(p),
.stage-copy__points.is-clamp :deep(.art__h),
.stage-copy__points.is-clamp :deep(.art__li) {
  margin: 0;
}

.stage-copy__more {
  display: inline-block;
  margin-top: 2px;
  padding: 0;
  border: none;
  background: transparent;
  color: #fff;
  font-size: 11px;
  line-height: 1.4;
  text-decoration: underline;
  text-underline-offset: 2px;
  cursor: pointer;
}

.player-fallback {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-2);
  padding: var(--sp-8) var(--sp-5);
  text-align: center;
  background: var(--bg-surface);
  color: var(--warning-text);
  border-bottom: 1px solid var(--border-light);
}

.player-fallback span {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
  line-height: 1.7;
  max-width: 320px;
}

.info {
  background: var(--bg-surface);
  padding: var(--sp-5) var(--sp-4);
}

.info__title {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: var(--fw-bold);
  line-height: 1.45;
}

.info__meta {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  flex-wrap: wrap;
  margin: var(--sp-3) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.info__intro {
  margin: var(--sp-3) 0 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  line-height: 1.75;
}

.info__speaker-org {
  margin: var(--sp-2) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

.article {
  margin-top: var(--sp-2);
  background: var(--bg-surface);
  padding: var(--sp-5) var(--sp-4);
}

.article__title {
  margin: 0 0 var(--sp-4);
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
}

.article__body :deep(p) {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-base);
  line-height: 1.85;
  color: var(--text-secondary);
}

.article__body :deep(.art__h) {
  margin: var(--sp-5) 0 var(--sp-3);
  padding-left: var(--sp-3);
  border-left: 3px solid var(--brand-primary);
  font-size: var(--fs-base);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.learn__footer {
  position: fixed;
  left: 50%;
  transform: translateX(-50%);
  bottom: 0;
  width: 100%;
  max-width: var(--mobile-max);
  /* 左右安全区：layout 为 max-width 容器，居中后水平方向有外溢，
     用 padding 抵消让 footer 内容跟着视口左右缩进 */
  padding: var(--sp-3) max(var(--sp-4), env(safe-area-inset-left, 0px))
    calc(var(--sp-3) + env(safe-area-inset-bottom, 0px))
    max(var(--sp-4), env(safe-area-inset-right, 0px));
  background: var(--bg-surface-solid);
  /* 不用 --bg-surface 半透明，避免背后视频穿透 */
  -webkit-backdrop-filter: blur(12px);
  backdrop-filter: blur(12px);
  border-top: 1px solid var(--border);
  z-index: 30;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.learn__dock {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--sp-2);
}

.learn__hint {
  margin: 0;
  max-width: 320px;
  text-align: center;
  font-size: var(--fs-xs);
  line-height: 1.5;
  color: var(--text-secondary);
}

@supports not ((-webkit-backdrop-filter: initial) or (backdrop-filter: initial)) {
  .learn__footer {
    background: var(--bg-surface-solid);
  }
}

.learn__bar-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
  margin-bottom: var(--sp-2);
}

.learn__bar-head b {
  color: var(--brand-primary);
  font-size: var(--fs-sm);
}

.learn__bar-head b.is-ok {
  color: var(--success-text);
}

.learn__bar-track {
  height: 6px;
  background: var(--bg-muted);
  border-radius: var(--r-full);
  overflow: hidden;
  margin-bottom: var(--sp-3);
}

.learn__bar-fill {
  height: 100%;
  background: var(--brand-primary);
  border-radius: var(--r-full);
  transition: width 0.4s ease;
}

.learn__bar-fill.is-ok {
  background: var(--success);
}
</style>
