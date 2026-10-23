<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { generateAdminShareLink, getAdminShareLinkDetail, revokeAdminShareLink } from '@/api'
import { SHARE_TTL_OPTIONS } from '@/utils/video/constants'
import { formatDateTime, isShareExpired } from '@/utils/video/share'

const DAY_MS = 24 * 60 * 60 * 1000

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  video: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close'])

const ttlDays = ref(SHARE_TTL_OPTIONS[0].days)
const link = ref(null)
const now = ref(Date.now())
const copied = ref(false)
const loading = ref(false)
const generating = ref(false)
const updating = ref(false)
const errorMessage = ref('')
let timer = null
let loadSeq = 0

const videoId = computed(() => props.video?.apiId ?? props.video?.id ?? null)
const hasLink = computed(() => Boolean(link.value))
const disabled = computed(() => link.value?.status === 0)
const expired = computed(() => hasLink.value && isShareExpired(link.value, now.value))
/** 撤回或过期都会让用户端入口不可用 */
const unavailable = computed(() => hasLink.value && (disabled.value || expired.value))
const statusText = computed(() => {
  if (loading.value) return '链接加载中…'
  if (!hasLink.value) return '尚未生成'
  if (disabled.value) return '链接已撤回'
  return expired.value ? '链接已过期' : '链接已生成'
})
const createdText = computed(() =>
  link.value?.createdAt ? formatDateTime(link.value.createdAt) : '',
)
const expiresText = computed(() => {
  if (!hasLink.value || !link.value.expiresAt) return ''
  return formatDateTime(link.value.expiresAt)
})
const remainingText = computed(() => {
  if (!hasLink.value || disabled.value || expired.value) return ''
  const remain = link.value.expiresAt - now.value
  const hours = Math.ceil(remain / (60 * 60 * 1000))
  if (hours <= 24) return `剩余 ${hours} 小时`
  return `剩余 ${Math.ceil(remain / DAY_MS)} 天`
})
const generateLabel = computed(() => {
  if (generating.value) return '生成中…'
  return hasLink.value ? '重新生成' : '生成链接'
})
const retractLabel = computed(() => (updating.value ? '撤回中…' : '撤回链接'))

watch(
  () => props.open,
  (open) => {
    if (open) start()
    else stop()
  },
  { immediate: true },
)

onUnmounted(stop)

/**
 * 后端时间形如 2026-10-26 10:00:00，统一转成毫秒时间戳。
 * 后端时区为 UTC 且不带时区后缀，直接 new Date 会被当成浏览器本地时间（差 8 小时），需补 Z。
 */
function parseDateTime(value) {
  if (value === null || value === undefined || value === '') return null
  if (typeof value === 'number') return value

  const text = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(text)) {
    const time = new Date(`${text.replace(' ', 'T')}Z`).getTime()
    return Number.isNaN(time) ? null : time
  }

  const time = new Date(text).getTime()
  return Number.isNaN(time) ? null : time
}

/** 补上弹窗展示需要的运行时字段 */
function decorateLink(item) {
  return {
    ...item,
    status: Number(item.status ?? 1) === 0 ? 0 : 1,
    createdAt: parseDateTime(item.created_at) ?? Date.now(),
    expiresAt: parseDateTime(item.expire_at),
  }
}

/** 已有链接的时效选中项尽量和接口返回的 expire_type（如 7d）对齐 */
function syncTtl(item) {
  const matched = /^(\d+)d$/.exec(String(item.expire_type ?? ''))
  const days =
    matched !== null
      ? Number(matched[1])
      : item.createdAt && item.expiresAt
        ? Math.round((item.expiresAt - item.createdAt) / DAY_MS)
        : null
  if (days !== null && SHARE_TTL_OPTIONS.some((option) => option.days === days)) {
    ttlDays.value = days
  }
}

function start() {
  if (!props.video) return

  copied.value = false
  errorMessage.value = ''
  link.value = null
  loading.value = false
  generating.value = false
  updating.value = false
  ttlDays.value = SHARE_TTL_OPTIONS[0].days
  now.value = Date.now()

  stop()
  timer = window.setInterval(() => {
    now.value = Date.now()
  }, 30 * 1000)

  loadLink()
}

function stop() {
  loadSeq += 1
  if (timer) {
    window.clearInterval(timer)
    timer = null
  }
}

/**
 * 打开弹窗时读取该视频当前生效的链接（无则返回 null）。
 * 生成属于写操作，交给用户主动点击。
 */
async function loadLink() {
  if (!videoId.value) return

  const seq = ++loadSeq
  loading.value = true
  try {
    const data = await getAdminShareLinkDetail({ videoId: videoId.value })
    if (seq !== loadSeq || !props.open) return

    link.value = data ? decorateLink(data) : null
    if (link.value) syncTtl(link.value)
  } catch (error) {
    if (seq === loadSeq && props.open) {
      errorMessage.value = error?.message || '链接加载失败，请稍后重试'
    }
  } finally {
    if (seq === loadSeq) loading.value = false
  }
}

async function generate() {
  if (!props.video || generating.value) return
  if (!videoId.value) {
    errorMessage.value = '视频信息不完整，无法生成链接'
    return
  }

  generating.value = true
  copied.value = false
  errorMessage.value = ''

  try {
    // 后端只支持 1 / 3 / 5 / 7 天；生成新链接时会自动失效该视频的旧链接
    const data = await generateAdminShareLink({
      videoId: videoId.value,
      expireDays: ttlDays.value,
    })
    link.value = decorateLink({
      ...data,
      created_at: data.created_at ?? Date.now(),
    })
    now.value = Date.now()
    syncTtl(link.value)
  } catch (error) {
    errorMessage.value = error?.message || '链接生成失败，请稍后重试'
  } finally {
    generating.value = false
  }
}

async function copyText(text) {
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(text)
    return
  }

  const input = document.createElement('textarea')
  input.value = text
  input.style.position = 'fixed'
  input.style.opacity = '0'
  document.body.appendChild(input)
  input.select()
  const ok = document.execCommand('copy')
  document.body.removeChild(input)
  if (!ok) throw new Error('copy failed')
}

async function copyLink() {
  if (!link.value || unavailable.value) return
  try {
    await copyText(link.value.url)
    copied.value = true
    window.setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch {
    errorMessage.value = '复制失败，请手动选中链接复制'
  }
}

/** 2.3 撤回链接：立即失效，用户无法再通过它访问；访问计数与答题数据保留 */
async function retract() {
  if (!videoId.value || !link.value?.id || updating.value) return

  updating.value = true
  errorMessage.value = ''
  try {
    await revokeAdminShareLink({ videoId: videoId.value })
    link.value = { ...link.value, status: 0 }
    copied.value = false
  } catch (error) {
    errorMessage.value = error?.message || '撤回失败，请稍后重试'
  } finally {
    updating.value = false
  }
}

function preview() {
  if (link.value?.url && !unavailable.value) {
    window.open(link.value.url, '_blank', 'noopener')
  }
}
</script>

<template>
  <div v-if="open" class="share-modal" @click.self="emit('close')">
    <div
      class="share-modal__dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="share-modal-title"
    >
      <header class="share-modal__head">
        <h2 id="share-modal-title" class="share-modal__title">生成视频链接</h2>
        <button type="button" class="share-modal__close" aria-label="关闭" @click="emit('close')">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            aria-hidden="true"
          >
            <path d="m6 6 12 12M18 6 6 18" />
          </svg>
        </button>
      </header>

      <div class="share-modal__body">
        <p class="share-modal__desc">
          为《{{
            video?.title
          }}》生成一条分享链接。生成后，用户端公众号入口会自动显示这条链接，用户点击即可答题、看视频。
        </p>

        <div class="ttl-row">
          <span class="ttl-row__label">链接时效</span>
          <div class="ttl-group">
            <button
              v-for="option in SHARE_TTL_OPTIONS"
              :key="option.days"
              type="button"
              class="ttl-btn"
              :class="{ 'ttl-btn--on': ttlDays === option.days }"
              @click="ttlDays = option.days"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <div
          class="share-card"
          :class="{ 'share-card--expired': unavailable, 'share-card--empty': !hasLink }"
        >
          <div class="share-card__head">
            <span class="share-card__status">
              <span class="share-card__dot"></span>
              {{ statusText }}
            </span>
            <span v-if="hasLink && createdText" class="share-card__time"
              >生成于 {{ createdText }}</span
            >
          </div>

          <div v-if="hasLink" class="share-card__link">
            <input :value="link.url" type="text" readonly @focus="$event.target.select()" />
            <button
              type="button"
              class="share-card__copy"
              :disabled="unavailable"
              @click="copyLink"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <rect x="9" y="9" width="11" height="11" rx="2" />
                <path d="M5 15V5a2 2 0 0 1 2-2h9" />
              </svg>
              {{ copied ? '已复制' : '复制' }}
            </button>
          </div>

          <p
            v-if="hasLink"
            class="share-card__hint"
            :class="{ 'share-card__hint--expired': unavailable }"
          >
            <template v-if="disabled">
              链接已撤回，用户端点击会提示「链接已失效」；如需继续分享请重新生成。
            </template>
            <template v-else-if="expired">
              链接已过期，用户端入口已自动隐藏，请重新生成。
            </template>
            <template v-else>
              {{ expiresText }} 到期（{{
                remainingText
              }}）。届时用户端入口自动隐藏，点击可直达「视频观看」。
            </template>
          </p>
          <p v-else-if="loading" class="share-card__hint">正在加载链接…</p>
          <p v-else class="share-card__hint">选择时效后点击下方「生成链接」。</p>
        </div>

        <p class="share-modal__tip">重新生成后，旧链接会立即失效。</p>
        <p v-if="errorMessage" class="share-modal__error">{{ errorMessage }}</p>
      </div>

      <footer class="share-modal__foot">
        <button type="button" class="btn btn--secondary" @click="emit('close')">关闭</button>

        <div class="share-modal__actions">
          <button
            v-if="hasLink && !unavailable"
            type="button"
            class="btn btn--ghost"
            :disabled="updating || generating"
            @click="retract"
          >
            {{ retractLabel }}
          </button>
          <button
            type="button"
            class="btn btn--secondary"
            :disabled="!hasLink || unavailable || loading"
            @click="copyLink"
          >
            复制链接
          </button>
          <button
            type="button"
            class="btn btn--secondary"
            :disabled="!hasLink || unavailable || loading"
            @click="preview"
          >
            预览用户端入口
          </button>
          <button
            type="button"
            class="btn btn--primary"
            :disabled="generating || loading"
            @click="generate"
          >
            {{ generateLabel }}
          </button>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.share-modal {
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

.share-modal__dialog {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 620px;
  max-height: calc(100vh - 48px);
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

/* ===== 标题栏 ===== */
.share-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-4);
  padding: var(--sp-5) var(--sp-6) var(--sp-2);
}

.share-modal__title {
  margin: 0;
  font-size: var(--fs-xl);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.share-modal__close {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  padding: 0;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.share-modal__close:hover {
  color: var(--text-primary);
  background: var(--bg-muted);
}

.share-modal__close svg {
  width: 20px;
  height: 20px;
}

/* ===== 内容 ===== */
.share-modal__body {
  display: flex;
  flex-direction: column;
  gap: var(--sp-4);
  padding: var(--sp-2) var(--sp-6) var(--sp-5);
  overflow-y: auto;
}

.share-modal__desc {
  margin: 0;
  font-size: var(--fs-sm);
  line-height: 1.7;
  color: var(--text-secondary);
}

.ttl-row {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
}

.ttl-row__label {
  flex-shrink: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-primary);
}

.ttl-group {
  display: inline-flex;
  padding: 3px;
  background: var(--bg-muted);
  border-radius: var(--r-md);
}

.ttl-btn {
  min-width: 62px;
  height: 32px;
  padding: 0 var(--sp-3);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: transparent;
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast),
    box-shadow var(--t-fast);
}

.ttl-btn:hover {
  color: var(--text-primary);
}

.ttl-btn--on {
  color: #fff;
  background: var(--brand-primary);
  box-shadow: var(--shadow-sm);
}

/* ===== 链接卡片 ===== */
.share-card {
  display: flex;
  flex-direction: column;
  gap: var(--sp-3);
  padding: var(--sp-4);
  background: var(--brand-tint);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  transition:
    background var(--t-fast),
    border-color var(--t-fast);
}

.share-card--expired {
  background: var(--danger-bg);
  border-color: rgb(230 57 70 / 0.18);
}

.share-card--empty {
  background: var(--bg-muted);
}

.share-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-3);
}

.share-card__status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  color: var(--success-text);
}

.share-card__dot {
  width: 7px;
  height: 7px;
  background: currentColor;
  border-radius: 50%;
}

.share-card--expired .share-card__status {
  color: var(--danger-text);
}

.share-card--empty .share-card__status {
  color: var(--text-secondary);
}

.share-card__time {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.share-card__link {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
}

.share-card__link input {
  flex: 1;
  min-width: 0;
  height: 38px;
  padding: 0 var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  outline: none;
}

.share-card__link input:focus {
  border-color: var(--brand-primary-hover);
}

.share-card__copy {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-1);
  flex-shrink: 0;
  height: 38px;
  padding: 0 var(--sp-3);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  cursor: pointer;
}

.share-card__copy svg {
  width: 14px;
  height: 14px;
}

.share-card__copy:hover:not(:disabled) {
  background: var(--brand-tint);
}

.share-card__copy:disabled {
  color: var(--text-disabled);
  cursor: not-allowed;
}

.share-card__hint {
  margin: 0;
  font-size: var(--fs-xs);
  line-height: 1.65;
  color: var(--text-secondary);
}

.share-card__hint--expired {
  color: var(--danger-text);
}

.share-modal__tip {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

.share-modal__error {
  margin: 0;
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-sm);
}

/* ===== 按钮 ===== */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  height: 38px;
  padding: 0 var(--sp-4);
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  border: 1px solid transparent;
  border-radius: var(--r-md);
  cursor: pointer;
  white-space: nowrap;
  transition:
    background var(--t-fast),
    box-shadow var(--t-fast),
    transform var(--t-fast);
}

.btn--secondary {
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border-color: var(--border);
  box-shadow: var(--shadow-sm);
}

.btn--secondary:hover:not(:disabled) {
  background: var(--bg-muted);
}

.btn--ghost {
  color: var(--text-secondary);
  background: transparent;
}

.btn--ghost:hover {
  color: var(--danger-text);
  background: var(--danger-bg);
}

.btn--primary {
  color: #fff;
  background: linear-gradient(
    165deg,
    var(--brand-primary-hover) 0%,
    var(--brand-primary) 55%,
    var(--brand-primary-active) 100%
  );
  box-shadow: 0 8px 20px var(--brand-glow);
}

.btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px var(--brand-glow);
}

.btn:disabled {
  box-shadow: none;
  cursor: not-allowed;
  opacity: 0.55;
}

/* ===== 底部 ===== */
.share-modal__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-3);
  padding: var(--sp-4) var(--sp-6);
  border-top: 1px solid var(--border);
}

.share-modal__actions {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* ===== 窄屏 ===== */
@media (max-width: 640px) {
  .share-modal {
    padding: var(--sp-3);
  }

  .share-modal__head,
  .share-modal__body,
  .share-modal__foot {
    padding-left: var(--sp-4);
    padding-right: var(--sp-4);
  }

  .share-modal__foot {
    flex-direction: column-reverse;
    align-items: stretch;
  }

  .share-modal__actions {
    justify-content: stretch;
  }

  .share-modal__actions .btn {
    flex: 1;
  }

  .share-card__link {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
