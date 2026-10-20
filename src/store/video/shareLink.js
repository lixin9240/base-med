import { DEFAULT_SHARE_TTL } from '@/utils/video/constants'
import { buildShareUrl, generateShareToken, isShareExpired } from '@/utils/video/share'

const SHARES_KEY = 'pv.videoShares'
const DAY_MS = 24 * 60 * 60 * 1000

function readShares() {
  try {
    const raw = localStorage.getItem(SHARES_KEY)
    if (!raw) return {}
    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch (error) {
    console.error('[shareLink] read failed', error)
    return {}
  }
}

function writeShares(shares) {
  try {
    localStorage.setItem(SHARES_KEY, JSON.stringify(shares))
    return true
  } catch (error) {
    console.error('[shareLink] write failed', error)
    return false
  }
}

/** 给分享记录补上运行时字段 */
function decorate(link) {
  if (!link) return null
  return {
    ...link,
    url: buildShareUrl({ courseId: link.courseId, token: link.token }),
    expired: isShareExpired(link),
  }
}

/** 读取某个视频当前生效/历史的分享链接 */
export function getShareLink(videoId) {
  const link = readShares()[String(videoId)]
  if (!link || link.revoked) return null
  return decorate(link)
}

/** 是否被主动撤回（撤回后再次打开弹窗不再自动生成） */
export function isShareRevoked(videoId) {
  return Boolean(readShares()[String(videoId)]?.revoked)
}

/**
 * 生成分享链接。同一视频只保留一条，重新生成会覆盖旧 token。
 */
export function generateShareLink(video, ttlDays = DEFAULT_SHARE_TTL) {
  if (!video?.id) throw new Error('视频不存在')

  const shares = readShares()
  const days = Number(ttlDays) || DEFAULT_SHARE_TTL
  const now = Date.now()
  const link = {
    token: generateShareToken(),
    videoId: video.id,
    courseId: video.courseId,
    ttlDays: days,
    createdAt: now,
    expiresAt: now + days * DAY_MS,
  }

  shares[String(video.id)] = link
  writeShares(shares)
  return decorate(link)
}

/** 撤回链接：旧链接立即失效，并保留撤回标记 */
export function revokeShareLink(videoId) {
  const shares = readShares()
  const key = String(videoId)
  if (!shares[key]) return false
  shares[key] = { revoked: true, revokedAt: Date.now() }
  writeShares(shares)
  return true
}

/** 彻底删除某个视频的分享记录（删除视频时使用） */
export function clearShareLink(videoId) {
  const shares = readShares()
  const key = String(videoId)
  if (!(key in shares)) return false
  delete shares[key]
  writeShares(shares)
  return true
}

/** 用户端校验 token，过期或不存在时返回 null */
export function resolveShareLink(token) {
  if (!token) return null
  const link = Object.values(readShares()).find((item) => item.token === token)
  if (!link || isShareExpired(link)) return null
  return decorate(link)
}
