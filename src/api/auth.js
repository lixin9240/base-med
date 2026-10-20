import { get, post } from './http'

const LOGIN_PATH = '/api/admin/auth/login'
const PROFILE_PATH = '/api/admin/auth/profile'
const LOGOUT_PATH = '/api/admin/auth/logout'

/**
 * 管理员登录。
 * 返回 { token, adminInfo }，其中 adminInfo 对应接口信封里的 admin_info 字段。
 */
export async function adminLogin({ username, password }) {
  const body = await post(LOGIN_PATH, { username, password })
  return {
    token: body?.data?.token ?? '',
    adminInfo: body?.data?.admin_info ?? null,
  }
}

/** 获取当前登录管理员信息，失败时返回 null */
export async function adminProfile() {
  const body = await get(PROFILE_PATH)
  return body?.data ?? null
}

/** 管理员登出，成功返回 true */
export async function adminLogout() {
  const body = await post(LOGOUT_PATH)
  return body?.success === true
}
