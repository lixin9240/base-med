/** 管理端登录态的持久化 key */
const TOKEN_KEY = 'pv.admin.token'
const INFO_KEY = 'pv.admin.info'

function read(key, fallback) {
  try {
    const raw = localStorage.getItem(key)
    if (raw === null) return fallback
    return JSON.parse(raw)
  } catch (error) {
    console.error('[admin/auth] read failed', key, error)
    return fallback
  }
}

function write(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value))
    return true
  } catch (error) {
    console.error('[admin/auth] write failed', key, error)
    return false
  }
}

/** 读取管理员 token，未登录时返回空字符串 */
export function getAdminToken() {
  const token = read(TOKEN_KEY, '')
  return typeof token === 'string' ? token : ''
}

/** 读取当前管理员信息，未登录时返回 null */
export function getAdminInfo() {
  return read(INFO_KEY, null)
}

/** 保存登录态 */
export function saveAdminAuth({ token, adminInfo }) {
  write(TOKEN_KEY, token ?? '')
  write(INFO_KEY, adminInfo ?? null)
}

/** 清除登录态 */
export function clearAdminAuth() {
  try {
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(INFO_KEY)
    return true
  } catch (error) {
    console.error('[admin/auth] clear failed', error)
    return false
  }
}
