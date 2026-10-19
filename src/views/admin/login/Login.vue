<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = ref({
  username: '',
  password: '',
  remember: false,
})

const loading = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)

function handleLogin() {
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
  // TODO: 调用后端登录接口
  setTimeout(() => {
    loading.value = false
    // mock 登录成功
    router.push('/admin')
  }, 800)
}

function togglePassword() {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <div class="login-page">
    <!-- 左侧品牌展示区 -->
    <aside class="brand-panel">
      <div class="brand-content">
        <div class="brand-logo">
          <!-- 内联 SVG 医疗十字 logo，后续可替换为设计稿图片 -->
          <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="64" height="64" rx="14" fill="#ffffff22" />
            <path
              d="M32 16v32M16 32h32"
              stroke="#fff"
              stroke-width="5"
              stroke-linecap="round"
            />
          </svg>
        </div>
        <h1 class="brand-title">Base-Med</h1>
        <h2 class="brand-subtitle">医疗管理系统</h2>
        <p class="brand-slogan">智慧医疗 · 高效管理 · 安全可靠</p>
      </div>
      <div class="brand-decoration" aria-hidden="true"></div>
      <div class="brand-footer">© 2026 Base-Med. All Rights Reserved.</div>
    </aside>

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
            <a href="#" class="forgot-link">忘记密码？</a>
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
  background: #f5f6f8;
}

/* ===== 左侧品牌区 ===== */
.brand-panel {
  position: relative;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 48px;
  background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #0ea5e9 100%);
  color: #fff;
  overflow: hidden;
}

.brand-content {
  position: relative;
  z-index: 1;
  text-align: center;
}

.brand-logo svg {
  width: 72px;
  height: 72px;
  margin-bottom: 24px;
}

.brand-title {
  font-size: 40px;
  font-weight: 700;
  margin: 0 0 4px;
  letter-spacing: 2px;
}

.brand-subtitle {
  font-size: 22px;
  font-weight: 500;
  margin: 0 0 16px;
  opacity: 0.95;
}

.brand-slogan {
  font-size: 15px;
  margin: 0;
  opacity: 0.8;
}

/* 装饰性圆形 */
.brand-decoration {
  position: absolute;
  top: -100px;
  right: -100px;
  width: 320px;
  height: 320px;
  border-radius: 50%;
  background: #ffffff15;
}

.brand-footer {
  position: absolute;
  bottom: 24px;
  font-size: 12px;
  opacity: 0.6;
}

/* ===== 右侧表单区 ===== */
.form-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 48px;
  background: #fff;
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
  font-weight: 600;
  margin: 0 0 8px;
  color: #1a1a1a;
}

.form-desc {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* ===== 表单 ===== */
.form-item {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 8px;
}

.input-wrapper {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 12px 40px 12px 14px;
  font-size: 15px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  color: #1a1a1a;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px #2563eb20;
}

.form-input:disabled {
  background: #f9fafb;
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
  background: #f3f4f6;
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
  color: #374151;
  cursor: pointer;
  user-select: none;
}

.remember input[type="checkbox"] {
  accent-color: #2563eb;
  cursor: pointer;
}

.forgot-link {
  font-size: 14px;
  color: #2563eb;
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
}

/* ===== 错误提示 ===== */
.error-msg {
  margin-bottom: 12px;
  padding: 8px 12px;
  font-size: 13px;
  color: #dc2626;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
}

/* ===== 登录按钮 ===== */
.login-btn {
  width: 100%;
  padding: 12px;
  font-size: 16px;
  font-weight: 600;
  color: #fff;
  background: #2563eb;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
  letter-spacing: 4px;
}

.login-btn:hover:not(:disabled) {
  background: #1d4ed8;
}

.login-btn:active:not(:disabled) {
  transform: translateY(1px);
}

.login-btn:disabled {
  background: #93c5fd;
  cursor: not-allowed;
}

/* ===== 响应式：窄屏改为上下布局 ===== */
@media (max-width: 768px) {
  .login-page {
    flex-direction: column;
  }

  .brand-panel {
    flex: none;
    padding: 48px 24px 32px;
  }

  .brand-title {
    font-size: 28px;
  }

  .brand-subtitle {
    font-size: 18px;
  }

  .form-panel {
    flex: 1;
    padding: 32px 24px 48px;
  }

  .brand-footer {
    position: static;
    margin-top: 24px;
  }
}
</style>
