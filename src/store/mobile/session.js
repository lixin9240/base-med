import { createPublicDr } from '@/api/public'

const DR_ID_KEY = 'pv.drId'

/**
 * 点击视频时签发匿名编号并缓存（每次点击均生成新的 dr_id）。
 * 缓存同时记录 videoId，避免跨视频复用导致后端「匿名编号与视频不匹配」。
 */
export async function issueDrId(videoId) {
  const drId = await createPublicDr(videoId)
  try {
    sessionStorage.setItem(DR_ID_KEY, JSON.stringify({ videoId: String(videoId), drId }))
  } catch {
    // 忽略存储失败
  }
  return drId
}

/**
 * 读取当前已签发的 dr_id。
 * 传入 videoId 时校验缓存归属，不属于该视频返回空串（调用方应重新签发）；
 * 不传或传空 videoId 时直接返回缓存的编号（如仅按 exam_record_id 查结果）。
 */
export function currentDrId(videoId) {
  let raw = ''
  try {
    raw = sessionStorage.getItem(DR_ID_KEY) || ''
  } catch {
    return ''
  }
  if (!raw) return ''
  let stored
  try {
    stored = JSON.parse(raw)
  } catch {
    // 旧版缓存只存了裸字符串，无法校验归属，直接丢弃
    return ''
  }
  if (!stored || typeof stored !== 'object' || !stored.drId) return ''
  if (videoId === undefined || videoId === null || String(videoId) === '') {
    return String(stored.drId)
  }
  return String(stored.videoId) === String(videoId) ? String(stored.drId) : ''
}

const SKIP_WATCH_KEY = 'pv.skipWatch'

/** 标记某视频跳过观看门槛（视频损坏/不可播放时） */
export function setSkipWatch(videoId) {
  try {
    const set = JSON.parse(sessionStorage.getItem(SKIP_WATCH_KEY) || '{}')
    set[String(videoId)] = true
    sessionStorage.setItem(SKIP_WATCH_KEY, JSON.stringify(set))
  } catch {
    // 忽略存储失败
  }
}

/** 该视频是否已标记跳过观看门槛 */
export function isSkipWatch(videoId) {
  try {
    const set = JSON.parse(sessionStorage.getItem(SKIP_WATCH_KEY) || '{}')
    return Boolean(set[String(videoId)])
  } catch {
    return false
  }
}
