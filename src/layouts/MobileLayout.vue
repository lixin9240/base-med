<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import ToastHost from '@/components/mobile/ToastHost.vue'
import { ensureLearner, ensureStoreSeed } from '@/store'
import '@/styles/tokens.css'
import '@/styles/mobile.css'

onMounted(() => {
  ensureStoreSeed()
  ensureLearner()
})
</script>

<template>
  <div class="mobile-layout">
    <main class="mobile-layout__main">
      <RouterView />
    </main>
    <ToastHost />
  </div>
</template>

<style scoped>
.mobile-layout {
  max-width: var(--mobile-max, 480px);
  min-height: 100vh;
  margin: 0 auto;
  background: var(--bg-page-gradient, var(--bg-page, #f3faf6));
  color: var(--text-primary);
}

.mobile-layout__main {
  padding: 1rem;
  min-height: 100vh;
}

</style>
