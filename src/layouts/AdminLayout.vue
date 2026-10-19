<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="admin-layout">
    <header class="admin-layout__header">管理端</header>
    <main class="admin-layout__main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.admin-layout__header {
  padding: 0.75rem 1rem;
  font-weight: 600;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
}

.admin-layout__main {
  flex: 1;
  padding: 1rem;
}
</style>
