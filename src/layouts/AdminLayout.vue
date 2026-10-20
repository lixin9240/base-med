<script setup>
import { RouterView } from 'vue-router'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import '@/styles/tokens.css'
</script>

<template>
  <div class="admin-layout">
    <AdminSidebar />
    <div class="admin-layout__content">
      <header class="admin-layout__header">
        <span>管理端</span>
        <RouterLink class="admin-layout__viewer" to="/mobile/home" target="_blank">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M14 4h6v6" />
            <path d="M20 4 11 13" />
            <path d="M19 13.5V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5.5" />
          </svg>
          查看用户端
        </RouterLink>
      </header>
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
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--sp-3) var(--sp-5);
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border);
}

.admin-layout__viewer {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  text-decoration: none;
}

.admin-layout__viewer svg {
  width: 15px;
  height: 15px;
}

.admin-layout__viewer:hover {
  color: var(--brand-primary-active);
  text-decoration: underline;
}

.admin-layout__main {
  flex: 1;
  padding: var(--sp-5);
}
</style>
