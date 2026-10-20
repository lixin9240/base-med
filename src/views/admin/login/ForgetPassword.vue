<script setup>
import { onBeforeUnmount, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import LoginBrandPanel from '@/components/admin/login/LoginBrandPanel.vue'
import '@/styles/tokens.css'

const router = useRouter()

const form = reactive({
  username: '',
  email: '',
  code: '',
  password: '',
  confirmPassword: '',
})

const show = reactive({
  password: false,
  confirmPassword: false,
})

const submitting = ref(false)
const sending = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const countdown = ref(0)
let countdownTimer = null
let redirectTimer = null

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function validateEmail() {
  if (!form.email.trim()) {
    errorMsg.value = '请输入邮箱'
    return false
  }
  if (!EMAIL_RE.test(form.email.trim())) {
    errorMsg.value = '邮箱格式不正确'
    return false
  }
  return true
}

function startCountdown() {
  countdown.value = 60
  clearInterval(countdownTimer)
  countdownTimer = setInterval(() => {
    countdown.value -= 1
    if (countdown.value <= 0) {
      clearInterval(countdownTimer)
      countdownTimer = null
    }
  }, 1000)
}

function handleSendCode() {
  errorMsg.value = ''
  successMsg.value = ''
  if (!form.username.trim()) {
    errorMsg.value = '请输入账号'
    return
  }
  if (!validateEmail()) return

  sending.value = true
  // TODO: 调用后端发送邮箱验证码接口
  setTimeout(() => {
    sending.value = false
    startCountdown()
    successMsg.value = '验证码已发送至邮箱，请注意查收'
  }, 600)
}

function handleSubmit() {
  errorMsg.value = ''
  successMsg.value = ''

  if (!form.username.trim()) {
    errorMsg.value = '请输入账号'
    return
  }
  if (!validateEmail()) return
  if (!form.code.trim()) {
    errorMsg.value = '请输入验证码'
    return
  }
  if (!form.password) {
    errorMsg.value = '请输入新密码'
    return
  }
  if (form.password.length < 6) {
    errorMsg.value = '密码长度不能少于 6 位'
    return
  }
  if (form.password !== form.confirmPassword) {
    errorMsg.value = '两次输入的密码不一致'
    return
  }

  submitting.value = true
  // TODO: 调用后端重置密码接口
  setTimeout(() => {
    submitting.value = false
    successMsg.value = '密码重置成功，即将返回登录页'
    redirectTimer = setTimeout(() => {
      router.push({ name: 'admin-login' })
    }, 1200)
  }, 800)
}

function togglePassword(key) {
  show[key] = !show[key]
}

onBeforeUnmount(() => {
  clearInterval(countdownTimer)
  clearTimeout(redirectTimer)
})
</script>

<template>
  <div class="login-page admin-layout">
    <!-- 左侧品牌展示区 -->
    <LoginBrandPanel />

    <!-- 右侧忘记密码表单区 -->
    <main class="form-panel">
      <div class="form-wrapper">
        <div class="form-header">
          <h3 class="form-title">忘记密码</h3>
          <p class="form-desc">验证账号与邮箱后，设置新的登录密码</p>
        </div>

        <form class="login-form" @submit.prevent="handleSubmit">
          <!-- 账号 -->
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
                :disabled="submitting"
              />
            </div>
          </div>

          <!-- 邮箱 -->
          <div class="form-item">
            <label class="form-label" for="email">邮箱</label>
            <div class="input-wrapper">
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="form-input"
                placeholder="请输入绑定邮箱"
                autocomplete="email"
                :disabled="submitting"
              />
            </div>
          </div>

          <!-- 验证码 -->
          <div class="form-item">
            <label class="form-label" for="code">验证码</label>
            <div class="code-row">
              <input
                id="code"
                v-model="form.code"
                type="text"
                class="form-input"
                placeholder="请输入邮箱验证码"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                :disabled="submitting"
              />
              <button
                type="button"
                class="send-code-btn"
                :disabled="sending || countdown > 0 || submitting"
                @click="handleSendCode"
              >
                <span v-if="sending">发送中...</span>
                <span v-else-if="countdown > 0">{{ countdown }}s 后重发</span>
                <span v-else>发送验证码</span>
              </button>
            </div>
          </div>

          <!-- 新密码 -->
          <div class="form-item">
            <label class="form-label" for="password">新密码</label>
            <div class="input-wrapper">
              <input
                id="password"
                v-model="form.password"
                :type="show.password ? 'text' : 'password'"
                class="form-input"
                placeholder="请输入新密码（至少 6 位）"
                autocomplete="new-password"
                :disabled="submitting"
              />
              <button
                type="button"
                class="toggle-pwd"
                @click="togglePassword('password')"
                :disabled="submitting"
                :aria-label="show.password ? '隐藏密码' : '显示密码'"
              >
                <span v-if="show.password">🙈</span>
                <span v-else>👁</span>
              </button>
            </div>
          </div>

          <!-- 确认密码 -->
          <div class="form-item">
            <label class="form-label" for="confirmPassword">确认密码</label>
            <div class="input-wrapper">
              <input
                id="confirmPassword"
                v-model="form.confirmPassword"
                :type="show.confirmPassword ? 'text' : 'password'"
                class="form-input"
                placeholder="请再次输入新密码"
                autocomplete="new-password"
                :disabled="submitting"
              />
              <button
                type="button"
                class="toggle-pwd"
                @click="togglePassword('confirmPassword')"
                :disabled="submitting"
                :aria-label="show.confirmPassword ? '隐藏密码' : '显示密码'"
              >
                <span v-if="show.confirmPassword">🙈</span>
                <span v-else>👁</span>
              </button>
            </div>
          </div>

          <!-- 错误 / 成功提示 -->
          <div v-if="errorMsg" class="error-msg">{{ errorMsg }}</div>
          <div v-if="successMsg" class="success-msg">{{ successMsg }}</div>

          <!-- 提交按钮 -->
          <button type="submit" class="submit-btn" :disabled="submitting">
            <span v-if="submitting">提交中...</span>
            <span v-else>重置密码</span>
          </button>

          <div class="form-footer">
            <RouterLink class="back-link" :to="{ name: 'admin-login' }">返回登录</RouterLink>
          </div>
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
  margin-bottom: 32px;
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
  margin-bottom: 18px;
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
  padding: 12px 14px;
  font-size: 15px;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--bg-surface-solid);
  color: var(--text-primary);
  transition: border-color var(--t-fast), box-shadow var(--t-fast);
}

.input-wrapper .form-input {
  padding-right: 40px;
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

/* 验证码行 */
.code-row {
  display: flex;
  gap: var(--sp-2);
}

.code-row .form-input {
  flex: 1;
  min-width: 0;
}

.send-code-btn {
  flex: none;
  padding: 0 14px;
  font-size: 14px;
  font-weight: var(--fw-medium);
  color: var(--brand-primary);
  background: var(--brand-tint);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  cursor: pointer;
  white-space: nowrap;
  transition: background var(--t-fast), color var(--t-fast);
}

.send-code-btn:hover:not(:disabled) {
  background: var(--brand-soft);
  color: var(--brand-primary-active);
}

.send-code-btn:disabled {
  color: var(--text-disabled);
  background: var(--bg-muted);
  cursor: not-allowed;
}

/* ===== 提示 ===== */
.error-msg,
.success-msg {
  margin-bottom: 12px;
  padding: 8px 12px;
  font-size: 13px;
  border-radius: var(--r-sm);
}

.error-msg {
  color: var(--danger-text);
  background: var(--danger-bg);
  border: 1px solid rgb(155 28 49 / 0.25);
}

.success-msg {
  color: var(--success-text);
  background: var(--success-bg);
  border: 1px solid rgb(64 145 108 / 0.25);
}

/* ===== 提交按钮 ===== */
.submit-btn {
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
  letter-spacing: 2px;
}

.submit-btn:hover:not(:disabled) {
  background: linear-gradient(165deg, var(--brand-primary) 0%, var(--brand-primary-active) 100%);
  box-shadow: 0 14px 32px var(--brand-glow);
}

.submit-btn:active:not(:disabled) {
  transform: translateY(1px);
}

.submit-btn:disabled {
  background: var(--accent-teal);
  box-shadow: none;
  cursor: not-allowed;
}

/* ===== 底部链接 ===== */
.form-footer {
  margin-top: var(--sp-4);
  text-align: center;
}

.back-link {
  font-size: 14px;
  color: var(--brand-primary);
  text-decoration: none;
}

.back-link:hover {
  color: var(--brand-primary-hover);
  text-decoration: underline;
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
