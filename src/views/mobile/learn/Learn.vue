<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import { formatArticleHtml } from '@/utils/article'
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

let maxTime = 0 // 最大观看位置（秒）
let reportTimer = null

const articleHtml = computed(() => formatArticleHtml(video.value?.articleBody || ''))

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
      articleBody: detail.text_key_points,
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
      await nextTick()
      await tryAutoPlay()
    }
    document.addEventListener('visibilitychange', onVisibility)
  } catch (e) {
    error.value = e?.message || '加载失败'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  clearInterval(reportTimer)
  document.removeEventListener('visibilitychange', onVisibility)
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
  if (!el.paused && !el.seeking && Number(el.playbackRate) === 1 && document.visibilityState === 'visible') {
    if (el.currentTime > maxTime) maxTime = el.currentTime
  }
  syncProgress()
}

function onSeeking() {
  const el = videoEl.value
  if (!el) return
  if (!canQuiz.value && el.currentTime > maxTime + 1.5) {
    el.currentTime = maxTime
    toast('请完整观看视频后再继续')
  }
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
  router.push({ name: 'mobile-quiz', query: { videoId, stage: 'post' } })
}
</script>

<template>
  <div class="learn">
    <PageNav title="观看视频" />

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
      <div v-if="video.videoUrl && !noVideoMode" class="player">
        <video
          ref="videoEl"
          class="player__el"
          :src="video.videoUrl"
          controls
          autoplay
          playsinline
          webkit-playsinline
          preload="metadata"
          :aria-label="`播放视频：${video.title}`"
          @timeupdate="onTimeUpdate"
          @seeking="onSeeking"
          @seeked="onSeeking"
          @ratechange="onRateChange"
          @ended="onEnded"
          @error="onVideoError"
        />
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

      <section class="info">
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

      <section v-if="video.articleBody" class="article">
        <h2 class="article__title">视频要点</h2>
        <div class="article__body" v-html="articleHtml" />
      </section>

      <div class="learn__footer">
        <div class="learn__bar">
          <div class="learn__bar-head">
            <span>{{ noVideoMode ? '已可答题' : '观看进度' }}</span>
            <b :class="{ 'is-ok': canQuiz }">{{ Math.min(100, Math.round(watched)) }}%</b>
          </div>
          <div class="learn__bar-track">
            <div class="learn__bar-fill" :class="{ 'is-ok': canQuiz }" :style="{ width: `${Math.min(100, watched)}%` }" />
          </div>
        </div>
        <button type="button" class="btn btn--primary btn--lg btn--block" :disabled="!canQuiz" @click="goQuiz">
          {{ canQuiz ? '去答题' : `请看完视频（${required}% 后可答题）` }}
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.learn {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg-page);
  padding-bottom: calc(150px + env(safe-area-inset-bottom, 0px));
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
  position: relative;
  background: #111827;
  /* 锁比例的视频，保留控制条可见 */
  max-height: 70vh;
  max-height: 70dvh;
  overflow: hidden;
}

.player__el {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  max-height: 70vh;
  max-height: 70dvh;
  object-fit: contain;
  background: #111827;
}

.player__tip {
  position: absolute;
  left: var(--sp-3);
  bottom: var(--sp-3);
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--warning-bg);
  color: var(--warning-text);
  font-size: var(--fs-xs);
  padding: 4px 10px;
  border-radius: var(--r-full);
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
