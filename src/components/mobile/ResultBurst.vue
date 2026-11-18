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
  hideTimer = window.setTimeout(() => {
    visible.value = false
  }, 1200)
  doneTimer = window.setTimeout(() => {
    emit('finished')
  }, 1700)
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
    <div class="burst__core">
      <span class="burst__ring" />
      <span class="burst__badge">
        <AppIcon :name="passed ? 'check' : 'x'" :size="40" />
      </span>
      <p class="burst__label">{{ passed ? '已通过' : '未通过' }}</p>
    </div>
  </div>
</template>

<style scoped>
.burst {
  position: fixed;
  inset: 0;
  z-index: 80;
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

.burst__core {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  animation: burst-pop 0.55s cubic-bezier(0.22, 1.4, 0.36, 1) forwards;
}

.burst__ring {
  position: absolute;
  top: 0;
  left: 50%;
  width: 96px;
  height: 96px;
  margin-left: -48px;
  border-radius: 50%;
  border: 2px solid currentColor;
  opacity: 0;
  transform: scale(0.55);
  animation: burst-ring 1.05s ease-out forwards;
  pointer-events: none;
}

.burst__badge {
  position: relative;
  width: 84px;
  height: 84px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 12px 32px rgba(20, 40, 30, 0.2);
}

.burst__label {
  margin: 0;
  font-size: 1.25em;
  font-weight: var(--fw-bold);
  letter-spacing: 0.06em;
  text-shadow: 0 1px 8px rgba(255, 255, 255, 0.85);
}

.burst--pass {
  color: var(--success);
}

.burst--pass .burst__badge {
  background: var(--success);
}

.burst--pass .burst__label {
  color: var(--success-text);
}

.burst--fail {
  color: var(--danger);
}

.burst--fail .burst__badge {
  background: var(--danger);
}

.burst--fail .burst__label {
  color: var(--danger-text);
}

.burst--fail .burst__core {
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
  .burst__core,
  .burst__ring {
    animation: none !important;
    transition: none !important;
  }
}
</style>
