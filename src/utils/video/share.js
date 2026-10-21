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
