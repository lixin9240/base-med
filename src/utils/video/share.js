/** 已部署的用户端站点。后端链接里的旧域名在展示、复制、预览时换成这里。 */
const USER_SITE_ORIGIN = (
  import.meta.env.VITE_H5_BASE_URL || 'http://139.155.154.172:5557'
).replace(/\/$/, '')

/**
 * 把分享链接的服务器前缀换成用户端地址。
 * 路径和查询参数保留，例如 /mobile/quiz?videoId=1&share=...
 */
export function rewriteShareOrigin(url) {
  if (!url) return ''
  try {
    const parsed = new URL(url, `${USER_SITE_ORIGIN}/`)
    return `${USER_SITE_ORIGIN}${parsed.pathname}${parsed.search}${parsed.hash}`
  } catch {
    return url
  }
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
