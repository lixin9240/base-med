<script setup>
import { useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'

defineProps({
  title: { type: String, default: '' },
  brand: { type: Boolean, default: false },
  // 是否显示左侧返回箭头
  showBack: { type: Boolean, default: true },
})

const router = useRouter()

function back() {
  if (window.history.length > 1) router.back()
  else router.replace('/mobile/home')
}
</script>

<template>
  <header class="pnav" :class="{ 'pnav--brand': brand }">
    <button v-if="showBack" type="button" class="pnav__icon-btn" aria-label="返回" @click="back">
      <AppIcon name="chevronLeft" :size="22" />
    </button>
    <span v-else class="pnav__spacer" />
    <div class="pnav__center">
      <h1 class="pnav__title">{{ title }}</h1>
    </div>
    <div class="pnav__right">
      <slot name="right" />
      <span v-if="!$slots.right" class="pnav__spacer" />
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
  text-align: center;
}

.pnav__title {
  margin: 0;
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
</style>
