<script setup>
import { RouterView } from 'vue-router'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import '@/styles/tokens.css'
</script>

<template>
  <div class="admin-layout">
    <AdminSidebar />
    <div class="admin-layout__content">
      <header class="admin-layout__header">管理端</header>
      <main class="admin-layout__main">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.admin-layout {
  min-height: 100vh;
  display: flex;
  background: var(--bg-page-gradient);
  color: var(--text-primary);
}

.admin-layout__content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.admin-layout__header {
  padding: var(--sp-3) var(--sp-5);
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border);
}

.admin-layout__main {
  flex: 1;
  padding: var(--sp-5);
}
</style>
