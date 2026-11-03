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

/**
 * 本地开发免登录。
 * 只认 Vite development 环境里的 VITE_SKIP_ADMIN_AUTH=true，生产构建不会带上这个值。
 */
export function isSkipAdminAuth() {
  return import.meta.env.VITE_SKIP_ADMIN_AUTH === 'true'
}

/** 免登录时的界面资料。不是后端签发的凭证，不写入 token。 */
const DEV_ADMIN_INFO = {
  id: 0,
  username: 'local',
  name: '本地预览',
  status: 1,
}

/** 免登录且本地还没有管理员资料时，补一份仅供界面显示的资料 */
export function ensureDevAdminProfile() {
  if (!isSkipAdminAuth()) return
  if (getAdminInfo()) return
  write(INFO_KEY, DEV_ADMIN_INFO)
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
