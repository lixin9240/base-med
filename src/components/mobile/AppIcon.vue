<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 20 },
})

const paths = {
  cross: 'M12 2v6M12 16v6M2 12h6M16 12h6',
  play: 'M8 5v14l11-7z',
  chevronRight: 'M9 6l6 6-6 6',
  chevronLeft: 'M15 6l-6 6 6 6',
  check: 'M5 12l4 4 10-10',
  checkCircle: 'M9 12l2 2 4-4M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z',
  xCircle: 'M15 9l-6 6M9 9l6 6M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z',
  info: 'M12 16v-4M12 8h.01M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z',
  shield: 'M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z',
  alert: 'M12 8v4M12 16h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0z',
  inbox: 'M4 4h16v12H4z M4 12h4l2 3h4l2-3h4',
  refresh: 'M4 4v5h5M20 20v-5h-5M5 19a9 9 0 0 0 14 1M19 5a9 9 0 0 0-14-1',
  arrowRight: 'M5 12h14M13 6l6 6-6 6',
  stethoscope: 'M6 3v7a6 6 0 0 0 12 0V3M12 16v3M8 22h8',
  heartPulse: 'M12 21s-6-4.5-8-8a4 4 0 0 1 7-2 4 4 0 0 1 7 2c-2 3.5-8 8-8 8z',
  monitor: 'M3 5h18v10H3z M8 19h8',
  activity: 'M22 12h-4l-3 7-4-14-3 7H2',
}

const d = computed(() => paths[props.name] ?? paths.info)
</script>

<template>
  <svg
    class="app-icon"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    aria-hidden="true"
  >
    <path
      :d="d"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
</template>

<style scoped>
.app-icon {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}
</style>
