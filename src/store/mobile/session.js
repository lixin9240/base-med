import { createPublicDr } from '@/api/public'

const DR_ID_KEY = 'pv.drId'

function readDrId() {
  try {
    return sessionStorage.getItem(DR_ID_KEY) || ''
  } catch {
    return ''
  }
}

function writeDrId(drId) {
  try {
    sessionStorage.setItem(DR_ID_KEY, drId)
    return true
  } catch {
    return false
  }
}

function tokenFromUrl() {
  return new URLSearchParams(window.location.search).get('token') || ''
}

/**
 * 获取当前匿名编号 dr_id。
 * 优先返回已缓存的；否则从 URL 的 ?token= 调 3.1 签发并缓存。
 */
export async function resolveDrId() {
  const cached = readDrId()
  if (cached) return cached

  const token = tokenFromUrl()
  if (!token) throw new Error('缺少访问令牌，请从分享链接进入')

  const drId = await createPublicDr(token)
  writeDrId(drId)
  return drId
}

/** 读取当前已缓存的 dr_id（不触发签发），无则返回空串 */
export function currentDrId() {
  return readDrId()
}
