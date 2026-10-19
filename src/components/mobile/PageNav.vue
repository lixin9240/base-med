<script setup>
import { useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'

defineProps({
  title: { type: String, default: '' },
  brand: { type: Boolean, default: false },
})

const router = useRouter()

function back() {
  if (window.history.length > 1) router.back()
  else router.replace('/mobile/home')
}
</script>

<template>
  <header class="pnav" :class="{ 'pnav--brand': brand }">
    <button type="button" class="pnav__icon-btn" aria-label="返回" @click="back">
      <AppIcon name="chevronLeft" :size="22" />
    </button>
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
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-2);
  padding: 0 var(--sp-2);
  background: var(--bg-surface);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
}

.pnav--brand {
  background: var(--brand-primary);
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
</style>
