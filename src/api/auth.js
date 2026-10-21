import { get, post } from './http'

const LOGIN_PATH = '/api/admin/auth/login'
const PROFILE_PATH = '/api/admin/auth/profile'
const LOGOUT_PATH = '/api/admin/auth/logout'
const FORGOT_PASSWORD_PATH = '/api/admin/auth/forgot-password'
const RESET_PASSWORD_PATH = '/api/admin/auth/reset-password'

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

/**
 * 2.1.4 发送重置密码验证码。
 * 邮箱随账号一并提交（当前后端按 username 查绑定邮箱发送）。
 * 返回 { expire_minutes }（验证码有效期，分钟）。
 */
export async function forgotAdminPassword({ username, email }) {
  const body = await post(FORGOT_PASSWORD_PATH, { username, email })

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('验证码发送失败：服务未返回标准数据')
  }
  return body.data ?? {}
}

/** 2.1.5 重置密码。payload：{ username, code, password }。成功返回 true */
export async function resetAdminPassword({ username, code, password }) {
  const body = await post(RESET_PASSWORD_PATH, { username, code, password })
  return body?.success === true
}
