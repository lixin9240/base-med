<script setup>
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const menuItems = [
  { label: '视频管理', name: 'admin-videos' },
  { label: '答题记录', name: 'admin-records' },
  { label: '数据统计', name: 'admin-stats' },
]

function handleLogout() {
  // TODO: 调用后端退出登录接口 / 清除 token
  router.push('/admin/login')
}
</script>

<template>
  <aside class="admin-sidebar">
    <div class="admin-sidebar__brand">Base-Med</div>

    <nav class="admin-sidebar__nav">
      <RouterLink
        v-for="item in menuItems"
        :key="item.name"
        :to="{ name: item.name }"
        class="admin-sidebar__item"
        :class="{ 'admin-sidebar__item--active': route.name === item.name }"
      >
        {{ item.label }}
      </RouterLink>
    </nav>

    <button class="admin-sidebar__logout" @click="handleLogout">退出登录</button>
  </aside>
</template>

<style scoped>
.admin-sidebar {
  width: 220px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: var(--brand-primary);
  color: #fff;
}

.admin-sidebar__brand {
  padding: var(--sp-4) var(--sp-5);
  font-size: var(--fs-lg);
  font-weight: var(--fw-bold);
  letter-spacing: 0.5px;
  border-bottom: 1px solid rgb(255 255 255 / 0.12);
}

.admin-sidebar__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--sp-1);
  padding: var(--sp-3);
}

.admin-sidebar__item {
  display: block;
  padding: 10px var(--sp-3);
  font-size: var(--fs-sm);
  color: rgb(255 255 255 / 0.75);
  text-decoration: none;
  border-radius: var(--r-md);
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.admin-sidebar__item:hover {
  color: #fff;
  background: rgb(255 255 255 / 0.08);
}

.admin-sidebar__item--active {
  color: var(--brand-soft-text);
  background: var(--brand-soft);
  font-weight: var(--fw-semibold);
}

.admin-sidebar__logout {
  margin: var(--sp-3);
  padding: 10px;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--accent-pink-soft);
  background: rgb(255 255 255 / 0.06);
  border: 1px solid rgb(255 255 255 / 0.15);
  border-radius: var(--r-md);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.admin-sidebar__logout:hover {
  color: #fff;
  background: rgb(255 255 255 / 0.12);
}
</style>
