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
  background: #fff;
  border-right: 1px solid #e2e8f0;
}

.admin-sidebar__brand {
  padding: 16px 20px;
  font-size: 20px;
  font-weight: 700;
  color: #2563eb;
  border-bottom: 1px solid #e2e8f0;
}

.admin-sidebar__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
}

.admin-sidebar__item {
  display: block;
  padding: 10px 12px;
  font-size: 15px;
  color: #374151;
  text-decoration: none;
  border-radius: 8px;
  transition: background 0.2s, color 0.2s;
}

.admin-sidebar__item:hover {
  background: #f3f4f6;
}

.admin-sidebar__item--active {
  color: #2563eb;
  background: #eff6ff;
  font-weight: 600;
}

.admin-sidebar__logout {
  margin: 12px;
  padding: 10px;
  font-size: 15px;
  color: #dc2626;
  background: #fff;
  border: 1px solid #fecaca;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.admin-sidebar__logout:hover {
  background: #fef2f2;
}
</style>
