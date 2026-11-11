<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AppIcon from '@/components/mobile/AppIcon.vue'

defineProps({
  /** true=通过特效，false=未通过特效 */
  passed: { type: Boolean, required: true },
})

const emit = defineEmits(['finished'])

const visible = ref(true)
let hideTimer = 0
let doneTimer = 0

function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

onMounted(() => {
  if (prefersReducedMotion()) {
    visible.value = false
    emit('finished')
    return
  }
  // 1.1s 开始淡出，约 1.6s 卸掉
  hideTimer = window.setTimeout(() => {
    visible.value = false
  }, 1100)
  doneTimer = window.setTimeout(() => {
    emit('finished')
  }, 1600)
})

onBeforeUnmount(() => {
  window.clearTimeout(hideTimer)
  window.clearTimeout(doneTimer)
})
</script>

<template>
  <div
    class="burst"
    :class="[
      passed ? 'burst--pass' : 'burst--fail',
      { 'is-hiding': !visible },
    ]"
    aria-hidden="true"
  >
    <span class="burst__ring" />
    <span class="burst__badge">
      <AppIcon :name="passed ? 'check' : 'alert'" :size="36" />
    </span>
  </div>
</template>

<style scoped>
.burst {
  position: absolute;
  inset: 0;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  background: transparent;
  opacity: 1;
  transition: opacity 0.45s ease;
}

.burst.is-hiding {
  opacity: 0;
}

.burst__ring {
  position: absolute;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  border: 2px solid currentColor;
  opacity: 0;
  transform: scale(0.55);
  animation: burst-ring 1.05s ease-out forwards;
}

.burst__badge {
  position: relative;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 10px 28px rgba(20, 40, 30, 0.18);
  transform: scale(0.35);
  animation: burst-pop 0.55s cubic-bezier(0.22, 1.4, 0.36, 1) forwards;
}

.burst--pass {
  color: var(--success);
}

.burst--pass .burst__badge {
  background: var(--success);
}

.burst--fail {
  color: var(--warning-text);
}

.burst--fail .burst__badge {
  background: #c9891a;
  animation:
    burst-pop 0.5s cubic-bezier(0.22, 1.4, 0.36, 1) forwards,
    burst-shake 0.42s ease-in-out 0.5s;
}

@keyframes burst-pop {
  0% {
    transform: scale(0.35);
    opacity: 0;
  }
  70% {
    transform: scale(1.08);
    opacity: 1;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes burst-ring {
  0% {
    transform: scale(0.55);
    opacity: 0.55;
  }
  100% {
    transform: scale(1.55);
    opacity: 0;
  }
}

@keyframes burst-shake {
  0%,
  100% {
    transform: translateX(0) scale(1);
  }
  25% {
    transform: translateX(-5px) scale(1);
  }
  50% {
    transform: translateX(5px) scale(1);
  }
  75% {
    transform: translateX(-3px) scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .burst,
  .burst__badge,
  .burst__ring {
    animation: none !important;
    transition: none !important;
  }
}
</style>
