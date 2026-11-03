<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import { toast } from '@/utils/toast'
import { currentDrId } from '@/store/mobile/session'
import { claimLotteryPrize, drawLottery, getLotteryInfo } from '@/api/public'

const SPIN_DURATION = 4200
const SECTOR_TONES = [
  { bg: '#d8f3dc', text: '#14532d' },
  { bg: '#b7e4c7', text: '#14532d' },
  { bg: '#d8f3dc', text: '#14532d' },
  { bg: '#b7e4c7', text: '#14532d' },
  { bg: '#f6d365', text: '#5c4400' },
  { bg: '#e8f5ee', text: '#5c6f65' },
]
const PRIZE_LINE_BREAK = {
  远程会诊一次: ['远程会诊', '一次'],
  口腔护理套装: ['口腔护理', '套装'],
  '健康咨询 30 分钟': ['健康咨询', '30 分钟'],
}

const route = useRoute()
const router = useRouter()
const videoId = String(route.query.videoId || '')
const examRecordId = String(route.query.examRecordId || '')

const drId = ref('')
const loading = ref(true)
const error = ref('')
const prizes = ref([])
const chances = ref({ total: 3, used: 0, remaining: 0 })
const winners = ref([])
const winnerIndex = ref(0)
const pendingClaim = ref(null)

const rotation = ref(0)
const spinning = ref(false)
const result = ref(null)
const resultOpen = ref(false)

const claimOpen = ref(false)
const claimRecord = ref(null)
const phone = ref('')
const claiming = ref(false)

const confettiCanvas = ref(null)
let tickerTimer = null
let spinTimer = null
let confettiRaf = null

const sectorAngle = computed(() => (prizes.value.length ? 360 / prizes.value.length : 60))

const wheelSectors = computed(() =>
  prizes.value.map((prize, index) => {
    const angle = sectorAngle.value
    const start = -90 - angle / 2 + index * angle
    const end = start + angle
    const first = polar(0, 0, 148, start)
    const second = polar(0, 0, 148, end)
    const tone = SECTOR_TONES[index % SECTOR_TONES.length]
    return {
      key: prize.id ?? index,
      color: tone.bg,
      textColor: tone.text,
      d: `M 0 0 L ${first.x} ${first.y} A 148 148 0 0 1 ${second.x} ${second.y} Z`,
      labelRotate: start + angle / 2 + 90,
      lines: PRIZE_LINE_BREAK[prize.name] || [prize.name],
    }
  }),
)

const currentWinner = computed(() => winners.value[winnerIndex.value] || null)

const drawLabel = computed(() => {
  if (spinning.value) return '转盘转动中…'
  if (!chances.value.remaining) return '今日机会已用完'
  return '开始抽奖'
})

const resultTitle = computed(() => {
  if (!result.value) return ''
  if (result.value.prize?.type === 'real') return '恭喜中奖'
  if (result.value.prize?.type === 'retry') return '再来一次'
  return '谢谢参与'
})

const resultDesc = computed(() => {
  if (!result.value) return ''
  if (result.value.prize?.type === 'real') return '请填写领奖手机号，方便工作人员联系您'
  if (result.value.prize?.type === 'retry') return '本次不消耗抽奖次数，可以再转一次'
  return '这次没有转中，明天还有机会'
})

// TODO: 后端暂未返回答题/中奖人数统计，先使用占位数据
const answeredPeople = 1286
const winnerPeople = 357

function polar(cx, cy, radius, deg) {
  const rad = (deg * Math.PI) / 180
  return { x: cx + radius * Math.cos(rad), y: cy + radius * Math.sin(rad) }
}

function startTicker() {
  clearInterval(tickerTimer)
  tickerTimer = window.setInterval(() => {
    if (winners.value.length > 1) winnerIndex.value = (winnerIndex.value + 1) % winners.value.length
  }, 3000)
}

async function loadInfo() {
  const data = await getLotteryInfo({
    dr_id: drId.value,
    video_id: videoId,
    exam_record_id: examRecordId,
  })
  prizes.value = (Array.isArray(data.prizes) ? [...data.prizes] : []).sort(
    (a, b) => Number(a.sector_index ?? 0) - Number(b.sector_index ?? 0),
  )
  chances.value = data.chances || { total: 3, used: 0, remaining: 0 }
  winners.value = Array.isArray(data.winners) ? data.winners : []
  pendingClaim.value = data.pending_claim || null
  if (winners.value.length) winnerIndex.value = 0
}

onMounted(async () => {
  const id = currentDrId()
  if (!id || !videoId) {
    router.replace('/mobile/home')
    return
  }
  drId.value = id
  try {
    await loadInfo()
    if (pendingClaim.value) openClaim(pendingClaim.value)
    startTicker()
  } catch (e) {
    error.value = e?.message || '加载失败'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  clearInterval(tickerTimer)
  clearTimeout(spinTimer)
  cancelAnimationFrame(confettiRaf)
})

async function startDraw() {
  if (spinning.value) return
  if (!chances.value.remaining) {
    toast('今日抽奖次数已用完，明天再来')
    return
  }
  if (!prizes.value.length) {
    toast('奖品加载中，请稍候')
    return
  }

  spinning.value = true
  resultOpen.value = false
  try {
    const data = await drawLottery({
      dr_id: drId.value,
      video_id: videoId,
      exam_record_id: examRecordId,
    })
    const sector = Number(data.prize?.sector_index) || 0
    const angle = sectorAngle.value
    const jitter = Math.random() * (angle - 24) - (angle - 24) / 2
    const targetMod = (((-sector * angle + jitter) % 360) + 360) % 360
    const currentMod = (((rotation.value % 360) + 360) % 360)
    let delta = targetMod - currentMod
    if (delta < 0) delta += 360

    rotation.value += 360 * 5 + delta
    chances.value = data.chances || chances.value
    result.value = data

    spinTimer = window.setTimeout(() => {
      spinning.value = false
      resultOpen.value = true
      if (data.prize?.type === 'real') fireConfetti()
    }, SPIN_DURATION)
  } catch (e) {
    spinning.value = false
    toast(e?.message || '抽奖失败')
  }
}

function closeResult() {
  resultOpen.value = false
}

function openClaim(record) {
  claimRecord.value = {
    record_id: record.record_id,
    prize_name: record.prize_name || record.prize?.name || '',
  }
  phone.value = ''
  claimOpen.value = true
}

function openClaimFromResult() {
  if (!result.value) return
  resultOpen.value = false
  openClaim({
    record_id: result.value.record_id,
    prize_name: result.value.prize?.name,
  })
}

function openPendingClaim() {
  if (pendingClaim.value) openClaim(pendingClaim.value)
}

function closeClaim() {
  claimOpen.value = false
}

function onPhoneInput(event) {
  phone.value = String(event.target.value || '').replace(/\D/g, '').slice(0, 11)
}

async function submitClaim() {
  if (claiming.value) return
  if (!/^1[3-9]\d{9}$/.test(phone.value)) {
    toast('请输入正确的 11 位手机号')
    return
  }
  claiming.value = true
  try {
    await claimLotteryPrize({
      dr_id: drId.value,
      record_id: claimRecord.value.record_id,
      phone: phone.value,
    })
    claimOpen.value = false
    pendingClaim.value = null
    toast('领取成功，请留意工作人员联系')
    await loadInfo()
  } catch (e) {
    toast(e?.message || '领取失败')
  } finally {
    claiming.value = false
  }
}

function fireConfetti() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  const canvas = confettiCanvas.value
  if (!canvas) return
  const width = window.innerWidth
  const height = window.innerHeight
  const dpr = window.devicePixelRatio || 1
  canvas.width = width * dpr
  canvas.height = height * dpr
  canvas.style.width = `${width}px`
  canvas.style.height = `${height}px`
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  ctx.scale(dpr, dpr)

  const colors = ['#1b4332', '#2d6a4f', '#52b788', '#f5c451', '#ffffff']
  const particles = Array.from({ length: 140 }, () => ({
    x: Math.random() * width,
    y: -20 - Math.random() * 120,
    vx: (Math.random() - 0.5) * 2.4,
    vy: 2 + Math.random() * 3.2,
    size: 5 + Math.random() * 6,
    rotation: Math.random() * Math.PI,
    vr: (Math.random() - 0.5) * 0.24,
    color: colors[Math.floor(Math.random() * colors.length)],
  }))
  const startedAt = performance.now()

  function frame(now) {
    ctx.clearRect(0, 0, width, height)
    particles.forEach((particle) => {
      particle.x += particle.vx
      particle.y += particle.vy
      particle.vy += 0.05
      particle.rotation += particle.vr
      ctx.save()
      ctx.translate(particle.x, particle.y)
      ctx.rotate(particle.rotation)
      ctx.fillStyle = particle.color
      ctx.fillRect(-particle.size / 2, -particle.size / 2, particle.size, particle.size * 0.6)
      ctx.restore()
    })
    if (now - startedAt < 2600) {
      confettiRaf = requestAnimationFrame(frame)
    } else {
      ctx.clearRect(0, 0, width, height)
    }
  }
  confettiRaf = requestAnimationFrame(frame)
}
</script>

<template>
  <div class="lottery">
    <PageNav title="幸运抽奖"
    :show-back="false"
    :answered-people="answeredPeople"
    :winner-people="winnerPeople"
    />

    <div v-if="error" class="empty">
      <div class="empty__icon">
        <AppIcon name="alert" :size="44" />
      </div>
      <p class="empty__text">{{ error }}</p>
    </div>

    <div v-else-if="loading" class="empty">
      <p class="empty__text">加载中…</p>
    </div>

    <template v-else>
      <section class="lot__hero">
        <p class="lot__eyebrow">答题闯关 · 健康好礼</p>
        <h1 class="lot__title">转动幸运轮盘</h1>
        <p class="lot__subtitle">每天 3 次机会，答完题就能抽</p>

        <div v-if="currentWinner" class="lot__ticker">
          <span class="lot__ticker-label">中奖播报</span>
          <Transition name="lot-fade" mode="out-in">
            <p :key="winnerIndex" class="lot__ticker-text">
              {{ currentWinner.phone }} 抽中「{{ currentWinner.prize_name }}」
            </p>
          </Transition>
        </div>
      </section>

      <section class="lot__stage">
        <div class="lot__wheel-wrap">
          <span class="lot__pointer" aria-hidden="true" />
          <div class="lot__wheel-disc" :style="{ '--wheel-rotation': `${rotation}deg` }">
            <svg class="lot__wheel" viewBox="-160 -160 320 320" role="img" aria-label="抽奖转盘">
              <circle cx="0" cy="0" r="151" fill="none" stroke="#d9a441" stroke-width="4" opacity="0.5" />
              <path
                v-for="sector in wheelSectors"
                :key="sector.key"
                :d="sector.d"
                :fill="sector.color"
                stroke="#ffffff"
                stroke-width="2"
              />
              <g
                v-for="sector in wheelSectors"
                :key="`label-${sector.key}`"
                :transform="`rotate(${sector.labelRotate}) translate(0, -98)`"
              >
                <text
                  v-for="(line, lineIndex) in sector.lines"
                  :key="line"
                  x="0"
                  :y="(lineIndex - (sector.lines.length - 1) / 2) * 14"
                  text-anchor="middle"
                  class="lot__wheel-text"
                  :fill="sector.textColor"
                >
                  {{ line }}
                </text>
              </g>
              <circle cx="0" cy="0" r="40" fill="#ffffff" opacity="0.4" />
            </svg>
          </div>
          <button
            type="button"
            class="lot__hub"
            :disabled="spinning"
            aria-label="开始抽奖"
            @click="startDraw"
          >
            {{ spinning ? '抽奖中' : '抽奖' }}
          </button>
        </div>

        <p class="lot__chances">
          今日剩余
          <b>{{ chances.remaining }}</b>
          / {{ chances.total }} 次
        </p>
        <button
          type="button"
          class="btn btn--primary btn--lg btn--block"
          :disabled="spinning || chances.remaining === 0"
          @click="startDraw"
        >
          {{ drawLabel }}
        </button>
        <p class="lot__hint">抽中「再来一次」不消耗次数</p>

        <div v-if="pendingClaim" class="lot__pending">
          <span>待领取：{{ pendingClaim.prize_name }}</span>
          <button type="button" @click="openPendingClaim">填写手机号</button>
        </div>
      </section>

      <section class="lot__rules">
        <h2>活动说明</h2>
        <ul>
          <li>每天 3 次抽奖机会，次日零点重置。</li>
          <li>抽中「再来一次」不消耗抽奖次数。</li>
          <li>抽中实物奖品后请填写 11 位手机号，方便工作人员联系。</li>
        </ul>
      </section>
    </template>

    <Transition name="lot-pop">
      <div v-if="resultOpen && result" class="lot-modal" role="dialog" aria-modal="true" aria-label="抽奖结果">
        <div class="lot-modal__mask" @click="closeResult" />
        <div class="lot-modal__panel">
          <div
            class="lot-modal__icon"
            :class="{
              'lot-modal__icon--win': result.prize?.type === 'real',
              'lot-modal__icon--retry': result.prize?.type === 'retry',
            }"
          >
            <AppIcon
              :name="result.prize?.type === 'real' ? 'checkCircle' : result.prize?.type === 'retry' ? 'refresh' : 'info'"
              :size="26"
            />
          </div>
          <h2 class="lot-modal__title">{{ resultTitle }}</h2>
          <p class="lot-modal__prize">{{ result.prize?.name }}</p>
          <p class="lot-modal__desc">{{ resultDesc }}</p>
          <button
            v-if="result.prize?.type === 'real'"
            type="button"
            class="btn btn--primary btn--lg btn--block"
            @click="openClaimFromResult"
          >
            填写领奖信息
          </button>
          <button v-else type="button" class="btn btn--primary btn--lg btn--block" @click="closeResult">
            知道了
          </button>
          <button
            v-if="result.prize?.type === 'real'"
            type="button"
            class="lot-modal__later"
            @click="closeResult"
          >
            稍后填写
          </button>
        </div>
      </div>
    </Transition>

    <div v-if="claimOpen" class="lot-modal" role="dialog" aria-modal="true" aria-label="领取奖品">
      <div class="lot-modal__mask" @click="closeClaim" />
      <div class="lot-modal__panel">
        <h2 class="lot-modal__title">填写领奖手机号</h2>
        <p class="lot-modal__prize">{{ claimRecord?.prize_name }}</p>
        <p class="lot-modal__desc">填写 11 位手机号，中奖信息会同步给工作人员</p>
        <input
          class="lot-modal__input"
          type="tel"
          inputmode="numeric"
          autocomplete="tel"
          maxlength="11"
          placeholder="请输入 11 位手机号"
          :value="phone"
          @input="onPhoneInput"
        />
        <button
          type="button"
          class="btn btn--primary btn--lg btn--block"
          :disabled="claiming"
          @click="submitClaim"
        >
          {{ claiming ? '提交中…' : '确认领取' }}
        </button>
        <button type="button" class="lot-modal__later" @click="closeClaim">稍后填写</button>
      </div>
    </div>

    <canvas ref="confettiCanvas" class="lot__confetti" aria-hidden="true" />
  </div>
</template>

<style scoped>
.lottery {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg-page);
  padding-bottom: calc(var(--sp-8) + env(safe-area-inset-bottom, 0px));
  margin: calc(-1 * var(--sp-4));
  margin-top: calc(-1 * var(--sp-4));
  margin-left: calc(-1 * max(var(--sp-4), env(safe-area-inset-left, 0px)));
  margin-right: calc(-1 * max(var(--sp-4), env(safe-area-inset-right, 0px)));
}

.lot__hero {
  padding: var(--sp-6) var(--sp-5) calc(var(--sp-8) + var(--sp-5));
  background:
    radial-gradient(circle at 88% 4%, rgb(245 196 81 / 0.32) 0%, transparent 46%),
    linear-gradient(160deg, #fbfefc 0%, var(--brand-tint) 42%, var(--brand-soft) 100%);
  color: var(--brand-primary);
  border-bottom: 1px solid rgb(27 67 50 / 0.06);
}

.lot__eyebrow {
  margin: 0;
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  letter-spacing: 2px;
  color: #8a6a1f;
}

.lot__title {
  margin: var(--sp-2) 0 0;
  font-size: var(--fs-2xl);
  font-weight: var(--fw-bold);
  letter-spacing: 1px;
  color: var(--brand-primary);
}

.lot__subtitle {
  margin: var(--sp-2) 0 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.lot__ticker {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  margin-top: var(--sp-4);
  padding: 7px 12px;
  border: 1px solid var(--border);
  border-radius: var(--r-full);
  background: rgb(255 255 255 / 0.78);
  box-shadow: var(--shadow-sm);
  font-size: var(--fs-xs);
  overflow: hidden;
}

.lot__ticker-label {
  flex-shrink: 0;
  color: #8a6a1f;
  font-weight: var(--fw-semibold);
}

.lot__ticker-text {
  flex: 1;
  min-width: 0;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--text-secondary);
}

.lot-fade-enter-active,
.lot-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.lot-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.lot-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.lot__stage {
  position: relative;
  z-index: 2;
  margin: calc(-1 * var(--sp-8)) var(--sp-4) 0;
  padding: var(--sp-5) var(--sp-4);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  background: var(--bg-surface-solid);
  box-shadow: var(--shadow-md);
}

.lot__wheel-wrap {
  position: relative;
  width: min(78vw, 320px);
  aspect-ratio: 1;
  margin: 0 auto;
}

.lot__wheel-disc {
  width: 100%;
  height: 100%;
  transform: rotate(var(--wheel-rotation));
  transition: transform 4.2s cubic-bezier(0.15, 0.85, 0.25, 1);
  will-change: transform;
}

.lot__wheel {
  display: block;
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 10px 22px rgb(27 67 50 / 0.18));
}

.lot__wheel-text {
  font-size: 11px;
  font-weight: var(--fw-bold);
  letter-spacing: 0.5px;
}

.lot__pointer {
  position: absolute;
  top: -10px;
  left: 50%;
  z-index: 3;
  width: 58px;
  height: 54px;
  transform: translateX(-50%);
  background: linear-gradient(180deg, #ffd77a 0%, #d9a441 100%);
  clip-path: polygon(50% 100%, 0 0, 100% 0);
  filter: drop-shadow(0 5px 8px rgb(122 92 0 / 0.28));
}

.lot__hub {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 4;
  width: 74px;
  height: 74px;
  transform: translate(-50%, -50%);
  border: 3px solid #f5c451;
  border-radius: 50%;
  background: radial-gradient(circle at 34% 28%, var(--brand-primary-hover) 0%, var(--brand-primary) 46%, var(--brand-primary-active) 100%);
  box-shadow: 0 8px 20px rgb(27 67 50 / 0.28);
  color: #fff;
  font-size: var(--fs-md);
  font-weight: var(--fw-bold);
  letter-spacing: 1px;
  cursor: pointer;
}

.lot__hub:disabled {
  opacity: 0.72;
  cursor: default;
}

.lot__chances {
  margin: var(--sp-4) 0 0;
  text-align: center;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.lot__chances b {
  margin: 0 2px;
  font-size: var(--fs-xl);
  font-weight: var(--fw-bold);
  color: var(--warning-text);
  font-variant-numeric: tabular-nums;
}

.lot__hint {
  margin: var(--sp-2) 0 0;
  text-align: center;
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

.lot__pending {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-2);
  margin-top: var(--sp-4);
  padding: var(--sp-2) var(--sp-3);
  border: 1px solid rgb(176 125 26 / 0.24);
  border-radius: var(--r-md);
  background: var(--warning-bg);
  font-size: var(--fs-xs);
  color: var(--warning-text);
}

.lot__pending button {
  flex-shrink: 0;
  border: none;
  background: transparent;
  color: var(--warning-text);
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  text-decoration: underline;
  cursor: pointer;
}

.lot__rules {
  margin: var(--sp-4);
  padding: var(--sp-4);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  background: var(--bg-surface);
}

.lot__rules h2 {
  margin: 0 0 var(--sp-2);
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
}

.lot__rules ul {
  margin: 0;
  padding-left: 18px;
  color: var(--text-secondary);
  font-size: var(--fs-xs);
  line-height: 1.9;
}

.lot-modal {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-5);
}

.lot-modal__mask {
  position: absolute;
  inset: 0;
  background: rgb(8 28 21 / 0.58);
  -webkit-backdrop-filter: blur(2px);
  backdrop-filter: blur(2px);
}

.lot-modal__panel {
  position: relative;
  width: min(100%, 340px);
  padding: var(--sp-6) var(--sp-5);
  border-radius: var(--r-xl);
  background: var(--bg-surface-solid);
  box-shadow: var(--shadow-lg);
  text-align: center;
}

.lot-pop-enter-active,
.lot-pop-leave-active {
  transition: opacity 0.24s ease;
}

.lot-pop-enter-active .lot-modal__panel,
.lot-pop-leave-active .lot-modal__panel {
  transition: transform 0.24s ease;
}

.lot-pop-enter-from,
.lot-pop-leave-to {
  opacity: 0;
}

.lot-pop-enter-from .lot-modal__panel,
.lot-pop-leave-to .lot-modal__panel {
  transform: scale(0.92);
}

.lot-modal__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 54px;
  height: 54px;
  margin: 0 auto var(--sp-3);
  border-radius: 50%;
  background: var(--bg-muted);
  color: var(--text-secondary);
}

.lot-modal__icon--win {
  background: var(--success-bg);
  color: var(--success-text);
}

.lot-modal__icon--retry {
  background: var(--brand-tint);
  color: var(--brand-primary);
}

.lot-modal__title {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: var(--fw-bold);
}

.lot-modal__prize {
  margin: var(--sp-2) 0 0;
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  color: var(--brand-primary);
}

.lot-modal__desc {
  margin: var(--sp-2) 0 var(--sp-4);
  font-size: var(--fs-xs);
  line-height: 1.7;
  color: var(--text-secondary);
}

.lot-modal__input {
  width: 100%;
  height: 46px;
  margin-bottom: var(--sp-3);
  padding: 0 var(--sp-4);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--bg-page);
  color: var(--text-primary);
  font-size: var(--fs-md);
  letter-spacing: 1px;
  text-align: center;
  outline: none;
}

.lot-modal__input:focus {
  border-color: var(--brand-primary-hover);
  box-shadow: 0 0 0 3px var(--brand-tint);
}

.lot-modal__input::placeholder {
  color: var(--text-disabled);
  letter-spacing: 0;
}

.lot-modal__later {
  margin-top: var(--sp-3);
  border: none;
  background: transparent;
  color: var(--text-secondary);
  font-size: var(--fs-xs);
  cursor: pointer;
}

.lot__confetti {
  position: fixed;
  inset: 0;
  z-index: 70;
  pointer-events: none;
}

@media (max-width: 360px) {
  .lot__hero {
    padding: var(--sp-5) var(--sp-4) calc(var(--sp-8) + var(--sp-3));
  }
  .lot__title {
    font-size: var(--fs-xl);
  }
  .lot__stage {
    margin-left: var(--sp-3);
    margin-right: var(--sp-3);
    padding: var(--sp-4) var(--sp-3);
  }
  .lot__wheel-wrap {
    width: min(80vw, 280px);
  }
  .lot__hub {
    width: 64px;
    height: 64px;
    font-size: var(--fs-sm);
  }
}
</style>
