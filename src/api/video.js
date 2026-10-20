import { get, post } from './http'

const LIST_PATH = '/api/admin/video/list'
const DETAIL_PATH = '/api/admin/video/detail'
const CREATE_PATH = '/api/admin/video/create'
const UPDATE_PATH = '/api/admin/video/update'
const UPDATE_STATUS_PATH = '/api/admin/video/update-status'

/** 视频列表（管理端）。返回 { list, total, page, pageSize }，list 内字段保持后端命名。 */
export async function listAdminVideos({ page = 1, pageSize = 20 } = {}) {
  const body = await get(`${LIST_PATH}?page=${page}&page_size=${pageSize}`)

  // 后端未就绪时可能拿到 HTML 等非信封响应，这种情况必须报错，不能当成「暂无数据」
  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('视频列表加载失败：服务未返回标准数据')
  }

  const data = body.data ?? {}
  return {
    list: Array.isArray(data.list) ? data.list : [],
    total: Number(data.total) || 0,
    page: Number(data.page) || page,
    pageSize: Number(data.page_size) || pageSize,
  }
}

/** 视频详情（编辑弹窗回填）。返回后端原始字段对象 */
export async function getAdminVideoDetail(id) {
  const body = await get(`${DETAIL_PATH}?id=${id}`)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('视频详情加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 新增视频（2.2.3），payload 用后端字段名。返回新建记录，含 { id } */
export async function createAdminVideo(payload) {
  const body = await post(CREATE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('视频创建失败：服务未返回标准数据')
  }
  return body.data
}

/** 编辑视频（2.2.4），payload 用后端字段名 */
export async function updateAdminVideo(payload) {
  const body = await post(UPDATE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('视频保存失败：服务未返回标准数据')
  }
  return body.data ?? null
}

/** 上架 / 下架切换，status：0 已下架，1 已上架 */
export async function updateAdminVideoStatus({ id, status }) {
  const body = await post(UPDATE_STATUS_PATH, { id, status })

  if (!body || typeof body !== 'object' || body.success !== true) {
    throw new Error('状态切换失败：服务未返回成功结果')
  }
  return true
}
