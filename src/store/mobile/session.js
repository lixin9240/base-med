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
 * mock 模式下无 token 也会本地签发（见 createPublicDr）。
 */
export async function resolveDrId() {
  const cached = readDrId()
  if (cached) return cached

  const drId = await createPublicDr(tokenFromUrl())
  writeDrId(drId)
  return drId
}

/** 读取当前已缓存的 dr_id（不触发签发），无则返回空串 */
export function currentDrId() {
  return readDrId()
}
