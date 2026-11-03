const STORAGE_KEY = 'pv.lottery'
const DAILY_LIMIT = 3

const PRIZES = [
  { id: 1, name: '远程会诊一次', type: 'real', weight: 4, sector_index: 0 },
  { id: 2, name: '专家挂号券', type: 'real', weight: 8, sector_index: 1 },
  { id: 3, name: '口腔护理套装', type: 'real', weight: 6, sector_index: 2 },
  { id: 4, name: '健康咨询 30 分钟', type: 'real', weight: 10, sector_index: 3 },
  { id: 5, name: '再来一次', type: 'retry', weight: 12, sector_index: 4 },
  { id: 6, name: '谢谢参与', type: 'none', weight: 27, sector_index: 5 },
]

const WINNERS = [
  { phone: '138****2468', prize_name: '专家挂号券' },
  { phone: '139****8801', prize_name: '口腔护理套装' },
  { phone: '137****6620', prize_name: '远程会诊一次' },
]

function todayKey() {
  const date = new Date()
  const pad = (value) => String(value).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

function readState() {
  const fresh = { date: todayKey(), used: 0, pending: null }
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
    if (saved?.date === fresh.date) {
      return {
        date: saved.date,
        used: Number(saved.used) || 0,
        pending: saved.pending || null,
      }
    }
  } catch {
    // 读不到就按当天新开一份
  }
  return fresh
}

function writeState(state) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
  } catch {
    // 忽略存储失败，本次页面内仍可抽
  }
}

function chancesOf(used) {
  return {
    total: DAILY_LIMIT,
    used,
    remaining: Math.max(0, DAILY_LIMIT - used),
  }
}

function pickPrize() {
  const total = PRIZES.reduce((sum, prize) => sum + prize.weight, 0)
  let cursor = Math.random() * total
  for (const prize of PRIZES) {
    cursor -= prize.weight
    if (cursor <= 0) return prize
  }
  return PRIZES[PRIZES.length - 1]
}

/** 本地奖盘、今日次数、播报和待领取奖品。不请求后端。 */
export function getLocalLotteryInfo() {
  const state = readState()
  return {
    prizes: PRIZES,
    chances: chancesOf(state.used),
    winners: WINNERS,
    pending_claim: state.pending,
  }
}

/** 按权重抽一次。再来一次不扣次数。实物奖记入待领取。 */
export function drawLocalLottery() {
  const state = readState()
  if (chancesOf(state.used).remaining <= 0) {
    throw new Error('今日抽奖次数已用完，明天再来')
  }

  const prize = pickPrize()
  const used = prize.type === 'retry' ? state.used : state.used + 1
  const recordId = `local-${Date.now()}`
  const pending = prize.type === 'real'
    ? { record_id: recordId, prize_name: prize.name }
    : state.pending

  writeState({ date: state.date, used, pending })

  return {
    prize,
    record_id: recordId,
    chances: chancesOf(used),
  }
}

/** 本地记下领奖手机号并清掉待领取。 */
export function claimLocalLottery({ record_id: recordId }) {
  const state = readState()
  if (state.pending && String(state.pending.record_id) === String(recordId)) {
    writeState({ ...state, pending: null })
  }
  return { success: true }
}
