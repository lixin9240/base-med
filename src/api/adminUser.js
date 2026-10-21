import { get, post } from './http'

const LIST_PATH = '/api/admin/admin-user/list'
const CREATE_PATH = '/api/admin/admin-user/create'
const UPDATE_PATH = '/api/admin/admin-user/update'
const DELETE_PATH = '/api/admin/admin-user/delete'

/** 用户列表（2.2.1）。返回 { list, total }，list 字段保持后端命名。 */
export async function listAdminUsers({ page = 1, pageSize = 20, keyword, status } = {}) {
  const params = new URLSearchParams({
    page: String(page),
    page_size: String(pageSize),
  })
  if (keyword !== undefined && keyword !== null && keyword !== '') {
    params.set('keyword', keyword)
  }
  if (status !== undefined && status !== null && status !== '') {
    params.set('status', String(status))
  }

  const body = await get(`${LIST_PATH}?${params.toString()}`)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('用户列表加载失败：服务未返回标准数据')
  }

  const data = body.data ?? {}
  return {
    list: Array.isArray(data.list) ? data.list : [],
    total: Number(data.total) || 0,
  }
}

/** 新增用户（2.2.2）。返回 { id } */
export async function createAdminUser(payload) {
  const body = await post(CREATE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('用户创建失败：服务未返回标准数据')
  }
  return body.data
}

/** 编辑用户（2.2.3）。payload 用后端字段名；不传 password 不重置密码 */
export async function updateAdminUser(payload) {
  const body = await post(UPDATE_PATH, payload)

  if (!body || typeof body !== 'object' || body.success === false) {
    throw new Error('用户保存失败：服务未返回成功结果')
  }
  return body
}

/** 删除用户（2.2.4，软删除）。不能删除当前登录账号，后端前置校验不通过会抛错 */
export async function deleteAdminUser({ id }) {
  const body = await post(DELETE_PATH, { id })

  if (!body || typeof body !== 'object' || body.success === false) {
    throw new Error('用户删除失败：服务未返回成功结果')
  }
  return true
}
