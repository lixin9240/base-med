/** 生成分享链接 token */
export function generateShareToken() {
  if (globalThis.crypto?.randomUUID) {
    return `s_${globalThis.crypto.randomUUID().replace(/-/g, '')}`
  }
  return `s_${Date.now().toString(36)}${Math.random().toString(36).slice(2, 10)}`
}

/**
 * 拼分享链接。使用当前域名 + 路由 base，避免域名变更后旧链接失效。
 */
export function buildShareUrl({ courseId, token }) {
  const base = (import.meta.env.BASE_URL || '/').replace(/\/?$/, '/')
  const url = new URL(`${base}mobile/learn`, window.location.origin)
  if (courseId) url.searchParams.set('courseId', courseId)
  if (token) url.searchParams.set('share', token)
  return url.toString()
}

/** 分享链接是否已过期 */
export function isShareExpired(link, now = Date.now()) {
  return !link?.expiresAt || now >= link.expiresAt
}

/** 时间戳格式化为 YYYY-MM-DD HH:mm:ss */
export function formatDateTime(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  const pad = (number) => String(number).padStart(2, '0')
  return [
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`,
    `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`,
  ].join(' ')
}
