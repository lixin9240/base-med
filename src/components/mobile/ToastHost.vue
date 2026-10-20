<script setup>
import { computed } from 'vue'
import { useToast } from '@/utils/toast'

const { message } = useToast()
const visible = computed(() => !!message.value)
</script>

<template>
  <div class="toast-host" :class="{ 'is-visible': visible }" role="status" aria-live="polite">
    <div class="toast">{{ message }}</div>
  </div>
</template>

<style scoped>
.toast-host {
  position: fixed;
  /* 让 toast 处于 PageNav 之下但不被其覆盖；
     top 留出 PageNav 高度 + 安全区，避免遮挡顶栏按钮 */
  top: calc(48px + env(safe-area-inset-top, 0px) + var(--sp-3));
  left: 50%;
  transform: translate(-50%, -8px);
  z-index: 4000;
  pointer-events: none;
  max-width: min(90vw, 420px);
  width: max-content;
  opacity: 0;
  transition:
    opacity var(--t-normal),
    transform var(--t-normal);
}

.toast-host.is-visible {
  opacity: 1;
  transform: translate(-50%, 0);
}

.toast {
  padding: 10px 16px;
  border-radius: var(--r-full);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
  font-size: var(--fs-sm);
  color: var(--text-primary);
  white-space: nowrap;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
