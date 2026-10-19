<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="mobile-layout">
    <main class="mobile-layout__main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.mobile-layout {
  max-width: 480px;
  min-height: 100vh;
  margin: 0 auto;
  background: #fff;
}

.mobile-layout__main {
  padding: 1rem;
}
</style>
