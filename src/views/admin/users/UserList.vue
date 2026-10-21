<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  createAdminUser,
  deleteAdminUser,
  listAdminUsers,
  updateAdminUser,
} from '@/api'

const PAGE_SIZES = [20, 50, 100]

const rows = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(PAGE_SIZES[0])
const keyword = ref('')
const statusFilter = ref('') // '' | 1 | 0
const loading = ref(false)
const loadError = ref('')

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / pageSize.value)))

const disabledCount = computed(() =>
  rows.value.filter((u) => Number(u.status) === 0).length,
)

/** 把 2.2.1 接口返回的一条记录转成列表展示字段 */
function toRow(item) {
  return {
    id: item.id,
    username: item.username || '',
    name: item.name || '',
    email: item.email || '',
    role: item.role || '',
    status: Number(item.status),
    lastLoginAt: item.last_login_at || '',
    createdAt: item.created_at || '',
  }
}

async function refreshUsers() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await listAdminUsers({
      page: page.value,
      pageSize: pageSize.value,
      keyword: keyword.value,
      status: statusFilter.value,
    })
    rows.value = data.list.map(toRow)
    total.value = data.total
  } catch (error) {
    rows.value = []
    total.value = 0
    loadError.value = error?.message || '用户列表加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

onMounted(refreshUsers)

function onFilterChange() {
  page.value = 1
  refreshUsers()
}

function setPageSize(size) {
  pageSize.value = size
  page.value = 1
  refreshUsers()
}

function goPage(next) {
  page.value = next
  refreshUsers()
}

// ===== 禁用 / 恢复（2.2.3 仅更新 status）=====
const togglingId = ref(null)

async function toggleStatus(user) {
  if (togglingId.value !== null) return
  const nextStatus = user.status === 1 ? 0 : 1
  togglingId.value = user.id
  try {
    await updateAdminUser({ id: user.id, status: nextStatus })
    user.status = nextStatus
  } catch (error) {
    loadError.value = error?.message || '状态切换失败，请稍后重试'
  } finally {
    togglingId.value = null
  }
}

// ===== 删除（2.2.4）=====
const deletingId = ref(null)

async function removeUser(user) {
  if (deletingId.value !== null) return
  if (!window.confirm(`确定要删除用户「${user.name || user.username}」吗？`)) return
  deletingId.value = user.id
  try {
    await deleteAdminUser({ id: user.id })
    refreshUsers()
  } catch (error) {
    loadError.value = error?.message || '删除失败，请稍后重试'
  } finally {
    deletingId.value = null
  }
}

// ===== 新增 / 编辑（2.2.2 / 2.2.3）=====
const formOpen = ref(false)
const formMode = ref('create') // 'create' | 'edit'
const formSubmitting = ref(false)
const formError = ref('')
const form = ref({
  id: null,
  username: '',
  password: '',
  name: '',
  email: '',
  role: 'admin',
  status: 1,
})

const FORM_DEFAULT = {
  id: null,
  username: '',
  password: '',
  name: '',
  email: '',
  role: 'admin',
  status: 1,
}

function openCreate() {
  formMode.value = 'create'
  form.value = { ...FORM_DEFAULT }
  formError.value = ''
  formOpen.value = true
}

function openEdit(user) {
  formMode.value = 'edit'
  form.value = {
    id: user.id,
    username: user.username,
    password: '',
    name: user.name,
    email: user.email,
    role: user.role || 'admin',
    status: user.status,
  }
  formError.value = ''
  formOpen.value = true
}

function closeForm() {
  formOpen.value = false
  form.value = { ...FORM_DEFAULT }
  formError.value = ''
}

function validateForm() {
  const f = form.value
  if (!f.username.trim()) return '请输入用户名'
  if (formMode.value === 'create' && !f.password) return '请输入密码'
  if (formMode.value === 'create' && (f.password.length < 6 || f.password.length > 32))
    return '密码长度需 6~32 位'
  if (!f.name.trim()) return '请输入姓名'
  if (f.password && (f.password.length < 6 || f.password.length > 32))
    return '密码长度需 6~32 位'
  return ''
}

async function submitForm() {
  const msg = validateForm()
  if (msg) {
    formError.value = msg
    return
  }
  formSubmitting.value = true
  formError.value = ''
  try {
    if (formMode.value === 'create') {
      await createAdminUser({
        username: form.value.username.trim(),
        password: form.value.password,
        name: form.value.name.trim(),
        email: form.value.email.trim() || undefined,
        role: form.value.role,
        status: form.value.status,
      })
    } else {
      const payload = {
        id: form.value.id,
        username: form.value.username.trim(),
        name: form.value.name.trim(),
        email: form.value.email.trim() || undefined,
        role: form.value.role,
        status: form.value.status,
      }
      // 编辑时只有传了 password 才重置密码
      if (form.value.password) payload.password = form.value.password
      await updateAdminUser(payload)
    }
    closeForm()
    refreshUsers()
  } catch (error) {
    formError.value = error?.message || '保存失败，请稍后重试'
  } finally {
    formSubmitting.value = false
  }
}
</script>

<template>
  <section class="users-page">
    <!-- 页头 -->
    <header class="users-page__head">
      <div class="users-page__intro">
        <h1 class="users-page__title">用户管理</h1>
        <p class="users-page__desc">管理后台账号，禁用后该用户将无法登录。</p>
      </div>

      <div class="users-page__stat">
        <span class="chip">共 {{ total }} 位用户</span>
        <span class="chip chip--danger">当前页已禁用 {{ disabledCount }} 位</span>
        <button type="button" class="btn btn--brand" @click="openCreate">新增用户</button>
      </div>
    </header>

    <p v-if="loadError" class="users-banner">{{ loadError }}</p>

    <!-- 筛选 + 每页条数 -->
    <div class="users-page__toolbar">
      <div class="toolbar__search">
        <input
          v-model="keyword"
          class="input"
          type="search"
          placeholder="搜索用户名 / 姓名"
          @keyup.enter="onFilterChange"
        />
        <button type="button" class="btn btn--secondary" @click="onFilterChange">搜索</button>
      </div>

      <select
        v-model="statusFilter"
        class="select"
        aria-label="按状态筛选"
        @change="onFilterChange"
      >
        <option value="">全部状态</option>
        <option value="1">正常</option>
        <option value="0">已禁用</option>
      </select>

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
        <span class="col col--role">角色</span>
        <span class="col col--status">状态</span>
        <span class="col col--login">最后登录</span>
        <span class="col col--ops">操作</span>
      </div>

      <div v-for="user in rows" :key="user.id" class="users-row">
        <span class="col col--name">
          <span class="avatar">{{ user.name.slice(0, 1) }}</span>
          <span class="name-block">
            <span class="name-line">{{ user.name }}</span>
            <span class="username-line">@{{ user.username }}</span>
          </span>
        </span>
        <span class="col col--email">{{ user.email || '—' }}</span>
        <span class="col col--role">{{ user.role || '—' }}</span>
        <span class="col col--status">
          <span class="badge" :class="user.status === 1 ? 'badge--success' : 'badge--danger'">
            <span class="badge__dot"></span>
            {{ user.status === 1 ? '正常' : '已禁用' }}
          </span>
        </span>
        <span class="col col--login">{{ user.lastLoginAt || '—' }}</span>
        <span class="col col--ops">
          <button type="button" class="link-btn" @click="openEdit(user)">编辑</button>
          <button
            type="button"
            class="link-btn"
            :class="user.status === 1 ? 'link-btn--danger' : 'link-btn--brand'"
            :disabled="togglingId === user.id"
            @click="toggleStatus(user)"
          >
            {{ togglingId === user.id ? '处理中…' : user.status === 1 ? '禁用登录' : '恢复登录' }}
          </button>
          <button
            type="button"
            class="link-btn link-btn--danger"
            :disabled="deletingId === user.id"
            @click="removeUser(user)"
          >
            {{ deletingId === user.id ? '删除中…' : '删除' }}
          </button>
        </span>
      </div>

      <div v-if="!rows.length" class="users-empty">
        {{ loading ? '加载中…' : '暂无用户' }}
      </div>
    </div>

    <!-- 分页 -->
    <footer v-if="total" class="users-page__foot">
      <div class="pager">
        <button
          type="button"
          class="pager__btn"
          :disabled="page <= 1 || loading"
          aria-label="上一页"
          @click="goPage(page - 1)"
        >
          ‹
        </button>
        <button
          v-for="n in totalPages"
          :key="n"
          type="button"
          class="pager__btn"
          :class="{ 'pager__btn--active': n === page }"
          :disabled="loading"
          @click="goPage(n)"
        >
          {{ n }}
        </button>
        <button
          type="button"
          class="pager__btn"
          :disabled="page >= totalPages || loading"
          aria-label="下一页"
          @click="goPage(page + 1)"
        >
          ›
        </button>
      </div>
      <span class="users-page__total">共 {{ total }} 条</span>
    </footer>

    <!-- 新增 / 编辑弹窗 -->
    <div v-if="formOpen" class="modal" @click.self="closeForm">
      <div class="modal__panel" role="dialog" aria-modal="true" aria-label="用户表单">
        <header class="modal__head">
          <h2 class="modal__title">{{ formMode === 'create' ? '新增用户' : '编辑用户' }}</h2>
          <button type="button" class="modal__close" aria-label="关闭" @click="closeForm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </button>
        </header>

        <div class="form">
          <label class="field">
            <span class="field__label">用户名 <span class="req">*</span></span>
            <input v-model="form.username" class="input" type="text" autocomplete="off" />
          </label>

          <label class="field">
            <span class="field__label">
              密码
              <span v-if="formMode === 'create'" class="req">*</span>
              <span v-else class="hint">留空则不修改</span>
            </span>
            <input v-model="form.password" class="input" type="password" autocomplete="new-password" />
          </label>

          <label class="field">
            <span class="field__label">姓名 <span class="req">*</span></span>
            <input v-model="form.name" class="input" type="text" />
          </label>

          <label class="field">
            <span class="field__label">邮箱</span>
            <input v-model="form.email" class="input" type="email" />
          </label>

          <label class="field">
            <span class="field__label">角色</span>
            <select v-model="form.role" class="select">
              <option value="admin">admin</option>
            </select>
          </label>

          <label class="field">
            <span class="field__label">状态</span>
            <select v-model.number="form.status" class="select">
              <option :value="1">正常</option>
              <option :value="0">已禁用</option>
            </select>
          </label>

          <p v-if="formError" class="form__error">{{ formError }}</p>
        </div>

        <footer class="modal__foot">
          <button type="button" class="btn btn--secondary" @click="closeForm">取消</button>
          <button
            type="button"
            class="btn btn--brand"
            :disabled="formSubmitting"
            @click="submitForm"
          >
            {{ formSubmitting ? '保存中…' : '保存' }}
          </button>
        </footer>
      </div>
    </div>
  </section>
</template>

<style scoped>
.users-page {
  display: flex;
  flex-direction: column;
  gap: var(--sp-6);
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
  gap: var(--sp-3);
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
  flex-wrap: wrap;
}

.toolbar__label {
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  margin-left: auto;
  padding-left: var(--sp-4);
  border-left: 1px solid var(--border);
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
  gap: var(--sp-5);
  padding: var(--sp-4) var(--sp-6);
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
  gap: var(--sp-3);
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

.col--status {
  flex: 0.9;
  justify-content: center;
}

.col--ops {
  flex: 0.7;
  gap: var(--sp-3);
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
  gap: var(--sp-5);
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

/* ===== 错误条 ===== */
.users-banner {
  margin: 0;
  padding: var(--sp-3) var(--sp-4);
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-md);
}

/* ===== 搜索 / 筛选 ===== */
.toolbar__search {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
}

.input,
.select {
  height: 34px;
  padding: 0 10px;
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  outline: none;
}

.input {
  width: 220px;
}

.input:focus,
.select:focus {
  border-color: var(--brand-primary-hover);
}

/* ===== 用户名双行展示 ===== */
.name-block {
  display: flex;
  flex-direction: column;
  line-height: 1.3;
}

.username-line {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

/* ===== 新增列宽 ===== */
.col--role {
  flex: 0.8;
  color: var(--text-secondary);
}

.col--login {
  flex: 1.1;
  color: var(--text-secondary);
  white-space: nowrap;
}

/* ===== 通用按钮 ===== */
.btn {
  height: 34px;
  padding: 0 var(--sp-4);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  border-radius: var(--r-md);
  border: 1px solid transparent;
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn--secondary {
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border-color: var(--border);
}

.btn--secondary:hover:not(:disabled) {
  background: var(--bg-muted);
}

.btn--brand {
  color: #fff;
  background: var(--brand-primary-hover);
}

.btn--brand:hover:not(:disabled) {
  background: var(--brand-primary-active);
}

/* ===== 弹窗 ===== */
.modal {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-4);
  background: rgba(15, 23, 42, 0.45);
}

.modal__panel {
  width: 100%;
  max-width: 460px;
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--sp-4) var(--sp-5);
  border-bottom: 1px solid var(--border);
}

.modal__title {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: var(--fw-semibold);
}

.modal__close {
  padding: 0;
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
}

.modal__close svg {
  width: 18px;
  height: 18px;
}

/* ===== 表单 ===== */
.form {
  display: flex;
  flex-direction: column;
  gap: var(--sp-4);
  padding: var(--sp-5);
}

.field {
  display: flex;
  flex-direction: column;
  gap: var(--sp-1);
}

.field__label {
  font-size: var(--fs-sm);
  color: var(--text-primary);
  font-weight: var(--fw-medium);
}

.req {
  color: var(--danger);
}

.hint {
  margin-left: var(--sp-1);
  font-size: var(--fs-xs);
  color: var(--text-secondary);
  font-weight: var(--fw-regular);
}

.field .input,
.field .select {
  width: 100%;
}

.form__error {
  margin: 0;
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-md);
}

.modal__foot {
  display: flex;
  justify-content: flex-end;
  gap: var(--sp-2);
  padding: var(--sp-4) var(--sp-5);
  border-top: 1px solid var(--border);
}

/* ===== 窄屏横向滚动 ===== */
@media (max-width: 1100px) {
  .users-table {
    overflow-x: auto;
  }

  .users-table__head,
  .users-row {
    min-width: 980px;
  }
}
</style>
