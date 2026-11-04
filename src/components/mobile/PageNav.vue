<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'

const props = defineProps({
  title: { type: String, default: '' },
  brand: { type: Boolean, default: false },
  // 是否显示左侧返回箭头
  showBack: { type: Boolean, default: true },
  // 已答题人数 / 已中奖人数，传 null 则不展示
  answeredPeople: { type: Number, default: null },
  winnerPeople: { type: Number, default: null },
  // 浮在全屏视频上：透明顶栏、浅色文字。默认关闭，其它页面外观不变
  overlay: { type: Boolean, default: false },
})

const router = useRouter()

const hasStats = computed(() => props.answeredPeople !== null || props.winnerPeople !== null)
const shownAnswered = ref(0)
const shownWinners = ref(0)
let statsRaf = null

function formatCount(value) {
  return Number(value || 0).toLocaleString('zh-CN')
}

// 数字从 0 快速递增，减少动态偏好时直接展示结果
function playStatsCount() {
  cancelAnimationFrame(statsRaf)
  const targetAnswered = Number(props.answeredPeople) || 0
  const targetWinners = Number(props.winnerPeople) || 0
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    shownAnswered.value = targetAnswered
    shownWinners.value = targetWinners
    return
  }

  const fromAnswered = shownAnswered.value
  const fromWinners = shownWinners.value
  const startedAt = performance.now()
  const duration = 900

  function frame(now) {
    const progress = Math.min(1, (now - startedAt) / duration)
    const eased = 1 - Math.pow(1 - progress, 3)
    shownAnswered.value = Math.round(fromAnswered + (targetAnswered - fromAnswered) * eased)
    shownWinners.value = Math.round(fromWinners + (targetWinners - fromWinners) * eased)
    if (progress < 1) statsRaf = requestAnimationFrame(frame)
  }
  statsRaf = requestAnimationFrame(frame)
}

onMounted(() => {
  if (hasStats.value) playStatsCount()
})

watch(
  () => [props.answeredPeople, props.winnerPeople],
  () => {
    if (hasStats.value) playStatsCount()
  },
)

onUnmounted(() => cancelAnimationFrame(statsRaf))

function back() {
  if (window.history.length > 1) router.back()
  else router.replace('/mobile/home')
}
</script>

<template>
  <header class="pnav" :class="{ 'pnav--brand': brand, 'pnav--stats': hasStats, 'pnav--overlay': overlay }">
    <button v-if="showBack" type="button" class="pnav__icon-btn" aria-label="返回" @click="back">
      <AppIcon name="chevronLeft" :size="22" />
    </button>
    <span v-else-if="!hasStats" class="pnav__spacer" />
    <div class="pnav__center">
      <h1 class="pnav__title">{{ title }}</h1>
    </div>
    <div class="pnav__right">
      <slot name="right" />
      <div v-if="hasStats" class="pnav__stats">
        <span class="pnav__stat">
          <b class="pnav__stat-num">{{ formatCount(shownAnswered) }}</b>
          <span class="pnav__stat-label">
            <span class="pnav__stat-dot" aria-hidden="true" />
            人已答题
          </span>
        </span>
        <span class="pnav__stat-divider" aria-hidden="true" />
        <span class="pnav__stat pnav__stat--win">
          <b class="pnav__stat-num">{{ formatCount(shownWinners) }}</b>
          <span class="pnav__stat-label">
            <span class="pnav__stat-dot" aria-hidden="true" />
            人已中奖
          </span>
        </span>
      </div>
      <span v-else-if="!$slots.right" class="pnav__spacer" />
    </div>
  </header>
</template>

<style scoped>
.pnav {
  position: sticky;
  top: 0;
  z-index: 20;
  /* 顶部安全区：刘海屏 / 灵动岛上方的留白 */
  padding-top: max(0px, env(safe-area-inset-top, 0px));
  min-height: calc(48px + env(safe-area-inset-top, 0px));
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-2);
  padding-left: var(--sp-2);
  padding-right: var(--sp-2);
  background: var(--bg-surface);
  /* 老安卓不支持 backdrop-filter 时降级为纯色背景 */
  background-color: var(--bg-surface-solid);
  -webkit-backdrop-filter: blur(12px);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
}

.pnav--brand {
  background-color: var(--brand-primary);
  border-bottom-color: transparent;
}

.pnav__icon-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: var(--r-md);
  background: transparent;
  color: var(--text-primary);
  cursor: pointer;
  /* 触屏按钮扩大可点击区域到 48px 安全标准 */
  -webkit-tap-highlight-color: transparent;
}

.pnav__icon-btn:hover {
  background: var(--bg-muted);
}

.pnav--brand .pnav__icon-btn {
  color: #fff;
}

.pnav--brand .pnav__icon-btn:hover {
  background: rgb(255 255 255 / 0.16);
}

.pnav__center {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  text-align: center;
}

.pnav__title {
  margin: 0;
  min-width: 0;
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  /* 系统字体下避免顶栏标题被截断 */
  line-height: 1.2;
}

.pnav--brand .pnav__title {
  color: #fff;
}

.pnav--stats .pnav__center {
  justify-content: flex-start;
  text-align: left;
}

.pnav--stats .pnav__right {
  min-width: 0;
}

/* 右侧两列数据账本：上方数字递增，下方圆点 + 标签 */
.pnav__stats {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.pnav__stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
}

.pnav__stat-num {
  font-size: var(--fs-base);
  font-weight: var(--fw-bold);
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.02em;
  color: var(--brand-primary);
  line-height: 1.2;
}

.pnav__stat--win .pnav__stat-num {
  color: var(--warning-text);
}

.pnav__stat-label {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  letter-spacing: 0.02em;
  color: var(--text-secondary);
  line-height: 1.2;
  white-space: nowrap;
}

.pnav__stat-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--success);
  flex-shrink: 0;
}

.pnav__stat--win .pnav__stat-dot {
  background: #c08a1e;
}

.pnav__stat-divider {
  width: 1px;
  height: 22px;
  background: var(--border);
}

.pnav--brand .pnav__stat-num {
  color: #fff;
}

.pnav--brand .pnav__stat--win .pnav__stat-num {
  color: #ffd77a;
}

.pnav--brand .pnav__stat-label {
  color: rgb(255 255 255 / 0.82);
}

.pnav--brand .pnav__stat-dot {
  background: #f5c451;
}

.pnav--brand .pnav__stat-divider {
  background: rgb(255 255 255 / 0.18);
}

@media (max-width: 380px) {
  .pnav--stats .pnav__title {
    font-size: var(--fs-sm);
  }
  .pnav__stats {
    gap: 8px;
  }
  .pnav__stat-num {
    font-size: var(--fs-sm);
  }
  .pnav__stat-divider {
    height: 20px;
  }
}

.pnav__right {
  min-width: 40px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.pnav__spacer {
  width: 40px;
}

@supports not ((-webkit-backdrop-filter: initial) or (backdrop-filter: initial)) {
  .pnav {
    background: var(--bg-surface-solid);
  }
}

/* 全屏播放时顶栏浮在画面上，不占独立一条白底 */
.pnav--overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 5;
  background: transparent;
  background-color: transparent;
  border-bottom: none;
  -webkit-backdrop-filter: none;
  backdrop-filter: none;
}

.pnav--overlay .pnav__title,
.pnav--overlay .pnav__stat-label {
  color: #fff;
  text-shadow: 0 1px 2px rgb(0 0 0 / 0.45);
}

.pnav--overlay .pnav__stat-num,
.pnav--overlay .pnav__stat--win .pnav__stat-num {
  color: #fff;
}

.pnav--overlay .pnav__stat-divider {
  background: rgb(255 255 255 / 0.35);
}

.pnav--overlay .pnav__stat-dot {
  background: #7dffa8;
}

.pnav--overlay .pnav__stat--win .pnav__stat-dot {
  background: #ffd77a;
}
</style>
