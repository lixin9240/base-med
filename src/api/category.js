import { get } from './http'

const LIST_PATH = '/api/admin/category/list'

/**
 * 分类列表（视频表单下拉用）。返回 [{ id, name }]。
 * 后端未就绪时可能拿到 HTML 等非信封响应，这种情况必须报错，不能当成「没有分类」。
 */
export async function listAdminCategories() {
  const body = await get(LIST_PATH)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('分类列表加载失败：服务未返回标准数据')
  }

  const raw = Array.isArray(body.data) ? body.data : body.data?.list
  if (!Array.isArray(raw)) {
    throw new Error('分类列表加载失败：服务返回格式不正确')
  }

  return raw
    .filter((item) => item && item.id !== undefined && item.id !== null && item.name)
    .map((item) => ({ id: item.id, name: String(item.name) }))
}
