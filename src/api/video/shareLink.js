import { get, post } from '../http'

// 访问链接模块接口（接口文档 2.4）
const CREATE_PATH = '/api/admin/share-link/create'
const LIST_PATH = '/api/admin/share-link/list'
const UPDATE_STATUS_PATH = '/api/admin/share-link/update-status'
const DELETE_PATH = '/api/admin/share-link/delete'

/**
 * 2.4.2 链接列表。
 * @param {{ page?: number, pageSize?: number, videoId?: number|string, status?: 0|1 }} params
 * @returns {Promise<{ list: Array, total: number }>} list 内字段保持后端命名
 */
export async function listAdminShareLinks({ page = 1, pageSize = 20, videoId, status } = {}) {
  const query = new URLSearchParams({
    page: String(page),
    page_size: String(pageSize),
  })
  if (videoId !== undefined && videoId !== null) query.set('video_id', String(videoId))
  if (status !== undefined && status !== null) query.set('status', String(status))

  const body = await get(`${LIST_PATH}?${query.toString()}`)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('链接列表加载失败：服务未返回标准数据')
  }

  const data = body.data ?? {}
  return {
    list: Array.isArray(data.list) ? data.list : [],
    total: Number(data.total) || 0,
  }
}

/**
 * 2.4.1 生成访问链接。
 * expire_type：permanent 永久 / 7d / 30d / custom 自定义；custom 时必须传 expire_at。
 * @returns {Promise<{ id: number, video_id: number, token: string, url: string, expire_type: string, expire_at: string|null }>}
 */
export async function createAdminShareLink({ video_id, org_id, expire_type, expire_at }) {
  const payload = { video_id, expire_type }
  if (org_id !== undefined && org_id !== null) payload.org_id = org_id
  if (expire_type === 'custom') payload.expire_at = expire_at

  const body = await post(CREATE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('链接生成失败：服务未返回标准数据')
  }
  return body.data
}

/**
 * 2.4.3 启用/失效链接。status：0 失效，1 启用；历史访问计数保留。
 * @param {{ id: number|string, status: 0|1 }} params
 */
export async function updateAdminShareLinkStatus({ id, status }) {
  const body = await post(UPDATE_STATUS_PATH, { id, status })

  if (!body || typeof body !== 'object' || body.success === false) {
    throw new Error(
      status === 1 ? '链接启用失败：服务未返回成功结果' : '链接失效失败：服务未返回成功结果',
    )
  }
  return true
}

/**
 * 2.4.4 删除链接（软删除）。已产生的访问记录与答题数据保留。
 * @param {{ id: number|string }} params
 */
export async function deleteAdminShareLink({ id }) {
  const body = await post(DELETE_PATH, { id })

  // 文档未约定返回体；HTTP 失败 / code!==0 已在 http 层抛错，这里只拦显式失败
  if (!body || typeof body !== 'object' || body.success === false) {
    throw new Error('链接删除失败：服务未返回成功结果')
  }
  return true
}
