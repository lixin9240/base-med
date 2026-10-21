<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { createAdminUser, updateAdminUser } from '@/api'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  /** 列表行数据（后端字段名）；新增时传 null */
  user: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'saved'])

const STATUS_OPTIONS = [
  { value: 1, label: '正常（可登录）' },
  { value: 0, label: '已禁用（不可登录）' },
]

const ROLES = [
  { value: 'admin', label: '管理员' },
  { value: 'operator', label: '运维' },
  { value: 'viewer', label: '访客' },
]

function createDraft(user) {
  return {
    id: user?.id ?? null,
    username: user?.username ?? '',
    name: user?.name ?? '',
    email: user?.email ?? '',
    role: user?.role || 'admin',
    status: Number(user?.status) === 0 ? 0 : 1,
    password: '',
    passwordConfirm: '',
  }
}

const draft = reactive(createDraft(null))
const errors = ref({})
const saving = ref(false)
const submitError = ref('')

const isEdit = computed(() => Boolean(draft.id))
const modalTitle = computed(() => (isEdit.value ? '编辑用户' : '新增用户'))
const submitLabel = computed(() => (saving.value ? '保存中…' : '保存'))

watch(
  () => props.open,
  (open) => {
    if (open) reset()
  },
  { immediate: true },
)

function reset() {
  Object.assign(draft, createDraft(props.user))
  errors.value = {}
  submitError.value = ''
  saving.value = false
}

function validate() {
  const next = {}

  const username = String(draft.username ?? '').trim()
  if (!username) next.username = '登录账号不能为空'
  else if (username.length > 32) next.username = '不能超过32字'

  const name = String(draft.name ?? '').trim()
  if (!name) next.name = '姓名不能为空'
  else if (name.length > 32) next.name = '不能超过32字'

  const email = String(draft.email ?? '').trim()
  if (email) {
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) next.email = '邮箱格式不正确'
    else if (email.length > 64) next.email = '不能超过64字'
  }

  const role = String(draft.role ?? '').trim()
  if (role.length > 16) next.role = '不能超过16字'

  const password = String(draft.password ?? '')
  if (!isEdit.value) {
    if (!password) next.password = '密码不能为空'
    else if (password.length < 6) next.password = '密码不能少于6位'
    else if (password.length > 32) next.password = '不能超过32位'
  } else if (password) {
    if (password.length < 6) next.password = '密码不能少于6位'
    else if (password.length > 32) next.password = '不能超过32位'
  }

  if (draft.passwordConfirm !== password) next.passwordConfirm = '两次输入不一致'

  errors.value = next
  return Object.keys(next).length === 0
}

async function submit() {
  if (saving.value) return
  if (!validate()) return

  saving.value = true
  submitError.value = ''

  const payload = {
    username: String(draft.username).trim(),
    name: String(draft.name).trim(),
    email: String(draft.email).trim(),
    role: String(draft.role).trim(),
    status: Number(draft.status),
  }

  try {
    if (isEdit.value) {
      payload.id = draft.id
      if (String(draft.password ?? '')) payload.password = String(draft.password)
      await updateAdminUser(payload)
    } else {
      payload.password = String(draft.password)
      await createAdminUser(payload)
    }
    emit('saved')
    emit('close')
  } catch (error) {
    submitError.value = error?.message || '保存失败，请重试'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div v-if="open" class="user-form" @click.self="emit('close')">
    <div
      class="user-form__dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="user-form-title"
    >
      <header class="user-form__head">
        <h2 id="user-form-title" class="user-form__title">{{ modalTitle }}</h2>
        <button type="button" class="user-form__close" aria-label="关闭" @click="emit('close')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6L6 18" />
          </svg>
        </button>
      </header>

      <form class="user-form__body" @submit.prevent="submit">
        <!-- 第一行：登录账号 + 姓名 -->
        <div class="form-row">
          <label class="field field--grow">
            <span class="field__label"><em class="req">*</em>登录账号</span>
            <input v-model="draft.username" type="text" maxlength="32" placeholder="用于登录的账号" />
            <p v-if="errors.username" class="field__error">{{ errors.username }}</p>
          </label>
          <label class="field field--grow">
            <span class="field__label"><em class="req">*</em>姓名</span>
            <input v-model="draft.name" type="text" maxlength="32" placeholder="真实姓名" />
            <p v-if="errors.name" class="field__error">{{ errors.name }}</p>
          </label>
        </div>

        <!-- 第二行：邮箱 + 角色 -->
        <div class="form-row">
          <label class="field field--grow">
            <span class="field__label">邮箱</span>
            <input v-model="draft.email" type="text" maxlength="64" placeholder="可选" />
            <p v-if="errors.email" class="field__error">{{ errors.email }}</p>
          </label>
          <label class="field field--grow">
            <span class="field__label">角色</span>
            <select v-model="draft.role">
              <option v-for="r in ROLES" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
            <p v-if="errors.role" class="field__error">{{ errors.role }}</p>
          </label>
        </div>

        <!-- 第三行：状态（独占一行，因为是重要开关） -->
        <div class="form-row form-row--single">
          <label class="field">
            <span class="field__label"><em class="req">*</em>状态</span>
            <select v-model.number="draft.status">
              <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </label>
        </div>

        <!-- 第四行：密码 + 确认密码 -->
        <div class="form-row">
          <label class="field field--grow">
            <span class="field__label">
              <em v-if="!isEdit" class="req">*</em>密码
            </span>
            <input
              v-model="draft.password"
              type="password"
              maxlength="32"
              placeholder="6-32 位"
            />
            <p v-if="errors.password" class="field__error">{{ errors.password }}</p>
          </label>
          <label class="field field--grow">
            <span class="field__label">确认密码</span>
            <input
              v-model="draft.passwordConfirm"
              type="password"
              maxlength="32"
              placeholder="再输入一次"
            />
            <p v-if="errors.passwordConfirm" class="field__error">{{ errors.passwordConfirm }}</p>
          </label>
        </div>

        <p v-if="submitError" class="form-error">{{ submitError }}</p>

        <footer class="user-form__foot">
          <button type="button" class="btn btn--secondary" @click="emit('close')">取消</button>
          <button type="submit" class="btn btn--primary" :disabled="saving">
            {{ submitLabel }}
          </button>
        </footer>
      </form>
    </div>
  </div>
</template>

<style scoped>
.user-form {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-5);
  background: rgb(8 28 21 / 0.45);
  backdrop-filter: blur(2px);
}

.user-form__dialog {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 720px;
  max-height: calc(100vh - 48px);
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.user-form__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--sp-5) var(--sp-6) var(--sp-2);
  border-bottom: 1px solid var(--border);
}

.user-form__title {
  margin: 0;
  font-size: var(--fs-xl);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.user-form__close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: background var(--t-fast), color var(--t-fast);
}

.user-form__close:hover {
  color: var(--text-primary);
  background: var(--bg-muted);
}

.user-form__close svg {
  width: 20px;
  height: 20px;
}

/* ===== 表单主体 ===== */
.user-form__body {
  display: flex;
  flex-direction: column;
  gap: var(--sp-4);
  padding: var(--sp-5) var(--sp-6) 0;
  overflow-y: auto;
}

.form-row {
  display: flex;
  align-items: flex-start;
  gap: var(--sp-4);
}

.form-row--single {
  max-width: 240px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
  flex: 1;
}

.field--grow {
  flex: 1;
}

.field__label {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-primary);
}

.req {
  font-style: normal;
  color: var(--danger);
}

.field input,
.field select {
  width: 100%;
  height: 42px;
  padding: 0 var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  outline: none;
  transition: border-color var(--t-fast), box-shadow var(--t-fast);
}

.field input::placeholder {
  color: var(--text-disabled);
}

.field input:focus,
.field select:focus {
  border-color: var(--brand-primary-hover);
  box-shadow: 0 0 0 3px var(--brand-tint);
}

.field__error {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--danger-text);
}

.form-error {
  margin: 0;
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-sm);
}

/* ===== 底部按钮 ===== */
.user-form__foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-3);
  padding: var(--sp-4) var(--sp-6) var(--sp-5);
  margin-top: var(--sp-2);
  border-top: 1px solid var(--border);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 96px;
  height: 40px;
  padding: 0 var(--sp-5);
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  border: 1px solid transparent;
  border-radius: var(--r-md);
  cursor: pointer;
  transition: background var(--t-fast), box-shadow var(--t-fast);
}

.btn--secondary {
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border-color: var(--border);
  box-shadow: var(--shadow-sm);
}

.btn--secondary:hover {
  background: var(--bg-muted);
}

.btn--primary {
  color: #fff;
  background: var(--brand-primary);
  box-shadow: 0 4px 16px rgb(0 122 77 / 0.22);
}

.btn--primary:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgb(0 122 77 / 0.28);
}

.btn:disabled {
  box-shadow: none;
  cursor: not-allowed;
  opacity: 0.65;
}

/* ===== 窄屏 ===== */
@media (max-width: 640px) {
  .user-form {
    padding: 0;
    align-items: flex-end;
  }

  .user-form__dialog {
    max-height: 90vh;
    border-radius: var(--r-xl) var(--r-xl) 0 0;
  }

  .form-row {
    flex-direction: column;
  }

  .form-row--single {
    max-width: 100%;
  }
}
</style>
