import { createPublicDr } from '@/api/public'

const DR_ID_KEY = 'pv.drId'

/**
 * 点击视频时签发匿名编号并缓存（每次点击均生成新的 dr_id）。
 */
export async function issueDrId(videoId) {
  const drId = await createPublicDr(videoId)
  try {
    sessionStorage.setItem(DR_ID_KEY, drId)
  } catch {
    // 忽略存储失败
  }
  return drId
}

/** 读取当前已签发的 dr_id，无则返回空串 */
export function currentDrId() {
  try {
    return sessionStorage.getItem(DR_ID_KEY) || ''
  } catch {
    return ''
  }
}
