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
  min-height: 100dvh;
  margin: 0 auto;
  background: var(--bg-page-gradient, var(--bg-page, #f3faf6));
  color: var(--text-primary);

  /* 顶部安全区：顶栏 sticky 时使用，calc 内部 bottom-pad 与 footer 共享 */
  --safe-top: env(safe-area-inset-top, 0px);
  --safe-bottom: env(safe-area-inset-bottom, 0px);
  --safe-left: env(safe-area-inset-left, 0px);
  --safe-right: env(safe-area-inset-right, 0px);
}

.mobile-layout__main {
  /* 横向 padding 走 token，纵向 padding 交给各页面自治；
     底部留 1sp 给 sticky footer 不被遮挡的安全余量。 */
  padding: var(--sp-4);
  padding-left: max(var(--sp-4), var(--safe-left));
  padding-right: max(var(--sp-4), var(--safe-right));
  padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
  min-height: 100vh;
  min-height: 100dvh;
}
</style>
