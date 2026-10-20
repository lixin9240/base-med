<script setup>
import { computed, ref } from 'vue'
import { adminUsers } from '@/mock/admin'
import { listDisabledUsers, saveDisabledUsers } from '@/store'

const PAGE_SIZES = [20, 50, 100]

// 禁用状态：优先取本地已保存的，首次进入沿用演示数据里的默认值
const stored = listDisabledUsers()
const disabledIds = ref(
  new Set(stored.length ? stored : adminUsers.filter((u) => u.disabled).map((u) => u.id)),
)

const users = computed(() =>
  adminUsers.map((u) => ({ ...u, disabled: disabledIds.value.has(u.id) })),
)

const disabledCount = computed(() => users.value.filter((u) => u.disabled).length)

const pageSize = ref(PAGE_SIZES[0])
const page = ref(1)

const totalPages = computed(() => Math.max(1, Math.ceil(users.value.length / pageSize.value)))

const paged = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return users.value.slice(start, start + pageSize.value)
})

function setPageSize(size) {
  pageSize.value = size
  page.value = 1
}

function toggleDisabled(user) {
  const next = new Set(disabledIds.value)
  if (user.disabled) next.delete(user.id)
  else next.add(user.id)
  disabledIds.value = next
  saveDisabledUsers(next)
}
</script>

<template>
  <section class="users-page">
    <!-- 页头 -->
    <header class="users-page__head">
      <div class="users-page__intro">
        <h1 class="users-page__title">用户管理</h1>
        <p class="users-page__desc">查看所有用户，禁用后该用户将无法登录。</p>
      </div>

      <div class="users-page__stat">
        <span class="chip">共 {{ users.length }} 位用户</span>
        <span class="chip chip--danger">已禁用 {{ disabledCount }} 位</span>
      </div>
    </header>

    <!-- 每页条数 -->
    <div class="users-page__toolbar">
      <span class="toolbar__label">每页显示</span>
      <div class="seg">
        <button
          v-for="size in PAGE_SIZES"
          :key="size"
          type="button"
          class="seg__btn"
          :class="{ 'seg__btn--active': size === pageSize }"
          @click="setPageSize(size)"
        >
          {{ size }} 条
        </button>
      </div>
    </div>

    <!-- 用户列表 -->
    <div class="users-table">
      <div class="users-table__head">
        <span class="col col--name">用户名</span>
        <span class="col col--email">邮箱</span>
        <span class="col col--phone">电话号码</span>
        <span class="col col--status">状态</span>
        <span class="col col--ops">操作</span>
      </div>

      <div v-for="user in paged" :key="user.id" class="users-row">
        <span class="col col--name">
          <span class="avatar">{{ user.name.slice(0, 1) }}</span>
          {{ user.name }}
        </span>
        <span class="col col--email">{{ user.email }}</span>
        <span class="col col--phone">{{ user.phone }}</span>
        <span class="col col--status">
          <span class="badge" :class="user.disabled ? 'badge--danger' : 'badge--success'">
            <span class="badge__dot"></span>
            {{ user.disabled ? '已禁用登录' : '正常' }}
          </span>
        </span>
        <span class="col col--ops">
          <button
            type="button"
            class="link-btn"
            :class="user.disabled ? 'link-btn--brand' : 'link-btn--danger'"
            @click="toggleDisabled(user)"
          >
            {{ user.disabled ? '恢复登录' : '禁用登录' }}
          </button>
        </span>
      </div>

      <div v-if="!paged.length" class="users-empty">暂无用户</div>
    </div>

    <!-- 分页 -->
    <footer class="users-page__foot">
      <div class="pager">
        <button
          type="button"
          class="pager__btn"
          :disabled="page <= 1"
          aria-label="上一页"
          @click="page -= 1"
        >
          ‹
        </button>
        <button
          v-for="n in totalPages"
          :key="n"
          type="button"
          class="pager__btn"
          :class="{ 'pager__btn--active': n === page }"
          @click="page = n"
        >
          {{ n }}
        </button>
        <button
          type="button"
          class="pager__btn"
          :disabled="page >= totalPages"
          aria-label="下一页"
          @click="page += 1"
        >
          ›
        </button>
      </div>
      <span class="users-page__total">共 {{ users.length }} 条</span>
    </footer>
  </section>
</template>

<style scoped>
.users-page {
  display: flex;
  flex-direction: column;
  gap: var(--sp-5);
}

/* ===== 页头 ===== */
.users-page__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--sp-4);
  flex-wrap: wrap;
}

.users-page__intro {
  display: flex;
  flex-direction: column;
  gap: var(--sp-1);
}

.users-page__title {
  margin: 0;
  font-size: var(--fs-2xl);
  font-weight: var(--fw-bold);
  color: var(--text-primary);
}

.users-page__desc {
  margin: 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.users-page__stat {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
}

.chip {
  padding: 5px 12px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-full);
  box-shadow: var(--shadow-sm);
}

.chip--danger {
  color: var(--danger-text);
  background: var(--danger-bg);
  border-color: transparent;
}

/* ===== 每页条数 ===== */
.users-page__toolbar {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
}

.toolbar__label {
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.seg {
  display: inline-flex;
  padding: 3px;
  background: var(--bg-muted);
  border-radius: var(--r-md);
}

.seg__btn {
  padding: 6px 16px;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  background: none;
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.seg__btn:hover {
  color: var(--text-primary);
}

.seg__btn--active {
  color: var(--brand-primary-hover);
  background: var(--bg-surface-solid);
  font-weight: var(--fw-semibold);
  box-shadow: var(--shadow-sm);
}

/* ===== 列表 ===== */
.users-table {
  display: flex;
  flex-direction: column;
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.users-table__head,
.users-row {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
  padding: var(--sp-3) var(--sp-5);
}

.users-table__head {
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-bottom: 1px solid var(--border);
}

.users-row {
  font-size: var(--fs-sm);
  border-bottom: 1px solid var(--border-light);
  transition: background var(--t-fast);
}

.users-row:last-of-type {
  border-bottom: none;
}

.users-row:hover {
  background: var(--brand-tint);
}

.col {
  display: flex;
  align-items: center;
  min-width: 0;
}

.col--name {
  flex: 1.1;
  gap: var(--sp-2);
  font-weight: var(--fw-medium);
  white-space: nowrap;
}

.avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  flex-shrink: 0;
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  color: var(--brand-primary);
  background: var(--brand-soft);
  border-radius: 50%;
}

.col--email {
  flex: 1.8;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.col--phone {
  flex: 1.1;
  color: var(--text-secondary);
  white-space: nowrap;
}

.col--status {
  flex: 0.9;
  justify-content: center;
}

.col--ops {
  flex: 0.7;
  justify-content: flex-end;
}

.users-empty {
  padding: var(--sp-10);
  text-align: center;
  font-size: var(--fs-sm);
  color: var(--text-disabled);
}

/* ===== 标签 ===== */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  border-radius: var(--r-full);
  white-space: nowrap;
}

.badge__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.badge--success {
  color: var(--success-text);
  background: var(--success-bg);
}

.badge--danger {
  color: var(--danger-text);
  background: var(--danger-bg);
}

/* ===== 操作链接 ===== */
.link-btn {
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  background: none;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  transition: color var(--t-fast);
}

.link-btn--danger {
  color: var(--danger-text);
}

.link-btn--danger:hover {
  color: var(--danger);
  text-decoration: underline;
}

.link-btn--brand {
  color: var(--brand-primary-hover);
}

.link-btn--brand:hover {
  color: var(--brand-primary-active);
  text-decoration: underline;
}

/* ===== 分页 ===== */
.users-page__foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-4);
}

.pager {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
}

.pager__btn {
  min-width: 32px;
  height: 32px;
  padding: 0 var(--sp-2);
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.pager__btn:hover:not(:disabled) {
  background: var(--bg-muted);
}

.pager__btn:disabled {
  color: var(--text-disabled);
  cursor: not-allowed;
}

.pager__btn--active {
  color: #fff;
  background: var(--brand-primary-hover);
  border-color: var(--brand-primary-hover);
}

.users-page__total {
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

/* ===== 窄屏横向滚动 ===== */
@media (max-width: 1100px) {
  .users-table {
    overflow-x: auto;
  }

  .users-table__head,
  .users-row {
    min-width: 860px;
  }
}
</style>
