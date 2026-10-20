<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { adminLogin } from '@/api'
import { saveAdminAuth } from '@/store/admin/auth'
import LoginBrandPanel from '@/components/admin/login/LoginBrandPanel.vue'
import '@/styles/tokens.css'

const router = useRouter()

const form = ref({
  username: '',
  password: '',
  remember: false,
})

const loading = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)

async function handleLogin() {
  errorMsg.value = ''
  if (!form.value.username.trim()) {
    errorMsg.value = '请输入账号'
    return
  }
  if (!form.value.password) {
    errorMsg.value = '请输入密码'
    return
  }

  loading.value = true
  try {
    const { token, adminInfo } = await adminLogin({
      username: form.value.username.trim(),
      password: form.value.password,
    })
    if (!token) throw new Error('登录失败：服务未返回登录凭证')

    saveAdminAuth({ token, adminInfo })
    router.push('/admin')
  } catch (error) {
    errorMsg.value = error?.message || '登录失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

function togglePassword() {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <div class="login-page admin-layout">
    <!-- 左侧品牌展示区 -->
    <LoginBrandPanel />

    <!-- 右侧登录表单区 -->
    <main class="form-panel">
      <div class="form-wrapper">
        <div class="form-header">
          <h3 class="form-title">管理员登录</h3>
          <p class="form-desc">请使用管理员账号登录系统</p>
        </div>

        <form class="login-form" @submit.prevent="handleLogin">
          <!-- 账号输入 -->
          <div class="form-item">
            <label class="form-label" for="username">账号</label>
            <div class="input-wrapper">
              <input
                id="username"
                v-model="form.username"
                type="text"
                class="form-input"
                placeholder="请输入管理员账号"
                autocomplete="username"
                :disabled="loading"
              />
            </div>
          </div>

          <!-- 密码输入 -->
          <div class="form-item">
            <label class="form-label" for="password">密码</label>
            <div class="input-wrapper">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="form-input"
                placeholder="请输入密码"
                autocomplete="current-password"
                :disabled="loading"
              />
              <button
                type="button"
                class="toggle-pwd"
                @click="togglePassword"
                :disabled="loading"
                :aria-label="showPassword ? '隐藏密码' : '显示密码'"
              >
                <span v-if="showPassword">🙈</span>
                <span v-else>👁</span>
              </button>
            </div>
          </div>

          <!-- 记住密码 + 忘记密码 -->
          <div class="form-options">
            <label class="remember">
              <input type="checkbox" v-model="form.remember" :disabled="loading" />
              <span>记住密码</span>
            </label>
            <RouterLink class="forgot-link" :to="{ name: 'admin-forget-password' }">
              忘记密码？
            </RouterLink>
          </div>

          <!-- 错误提示 -->
          <div v-if="errorMsg" class="error-msg">{{ errorMsg }}</div>

          <!-- 登录按钮 -->
          <button
            type="submit"
            class="login-btn"
            :disabled="loading"
          >
            <span v-if="loading">登录中...</span>
            <span v-else>登 录</span>
          </button>
        </form>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* ===== 整体布局 ===== */
.login-page {
  display: flex;
  min-height: 100vh;
  background: var(--bg-page-gradient);
}

/* ===== 右侧表单区 ===== */
.form-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 48px;
  background: transparent;
}

.form-wrapper {
  width: 100%;
  max-width: 400px;
}

.form-header {
  margin-bottom: 40px;
}

.form-title {
  font-size: 26px;
  font-weight: var(--fw-semibold);
  margin: 0 0 8px;
  color: var(--text-primary);
}

.form-desc {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0;
}

/* ===== 表单 ===== */
.form-item {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.input-wrapper {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 12px 40px 12px 14px;
  font-size: 15px;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--bg-surface-solid);
  color: var(--text-primary);
  transition: border-color var(--t-fast), box-shadow var(--t-fast);
}

.form-input:focus {
  outline: none;
  border-color: var(--brand-primary-hover);
  box-shadow: 0 0 0 3px var(--brand-tint);
}

.form-input::placeholder {
  color: var(--text-disabled);
}

.form-input:disabled {
  background: var(--bg-muted);
  cursor: not-allowed;
}

.toggle-pwd {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 32px;
  height: 32px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 16px;
  border-radius: 4px;
}

.toggle-pwd:hover {
  background: var(--bg-muted);
}

/* ===== 选项行 ===== */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.remember {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: var(--text-secondary);
  cursor: pointer;
  user-select: none;
}

.remember input[type="checkbox"] {
  accent-color: var(--brand-primary-hover);
  cursor: pointer;
}

.forgot-link {
  font-size: 14px;
  color: var(--brand-primary);
  text-decoration: none;
}

.forgot-link:hover {
  color: var(--brand-primary-hover);
  text-decoration: underline;
}

/* ===== 错误提示 ===== */
.error-msg {
  margin-bottom: 12px;
  padding: 8px 12px;
  font-size: 13px;
  color: var(--danger-text);
  background: var(--danger-bg);
  border: 1px solid rgb(155 28 49 / 0.25);
  border-radius: var(--r-sm);
}

/* ===== 登录按钮 ===== */
.login-btn {
  width: 100%;
  padding: 12px;
  font-size: 16px;
  font-weight: var(--fw-semibold);
  color: #fff;
  background: linear-gradient(
    165deg,
    var(--brand-primary-hover) 0%,
    var(--brand-primary) 55%,
    var(--brand-primary-active) 100%
  );
  box-shadow: 0 10px 28px var(--brand-glow);
  border: none;
  border-radius: var(--r-md);
  cursor: pointer;
  transition: background var(--t-normal), transform var(--t-fast), box-shadow var(--t-normal);
  letter-spacing: 4px;
}

.login-btn:hover:not(:disabled) {
  background: linear-gradient(165deg, var(--brand-primary) 0%, var(--brand-primary-active) 100%);
  box-shadow: 0 14px 32px var(--brand-glow);
}

.login-btn:active:not(:disabled) {
  transform: translateY(1px);
}

.login-btn:disabled {
  background: var(--accent-teal);
  box-shadow: none;
  cursor: not-allowed;
}

/* ===== 响应式：窄屏改为上下布局 ===== */
@media (max-width: 768px) {
  .login-page {
    flex-direction: column;
  }

  .form-panel {
    flex: 1;
    padding: 32px 24px 48px;
  }
}
</style>
