import { get, post } from '../http'

// 视频分享链接模块接口（《视频分享链接模块接口文档》2.x）
const GENERATE_PATH = '/api/admin/share-link/generate'
const DETAIL_PATH = '/api/admin/share-link/detail'
const REVOKE_PATH = '/api/admin/share-link/revoke'

/**
 * 2.1 生成 / 重新生成视频分享链接。
 * 同一视频已有生效链接时，后端会先自动失效旧链接再创建新链接。
 * @param {{ videoId: number|string, expireDays: 1|3|5|7 }} params
 * @returns {Promise<{ id: number, video_id: number, url: string, token: string, expire_type: string, expire_at: string, visit_count: number, status: number, created_at: string }>}
 */
export async function generateAdminShareLink({ videoId, expireDays }) {
  const body = await post(GENERATE_PATH, {
    video_id: videoId,
    expire_days: expireDays,
  })

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('链接生成失败：服务未返回标准数据')
  }
  return body.data
}

/**
 * 2.2 获取当前生效链接详情。无生效链接时后端返回 data: null。
 * @param {{ videoId: number|string }} params
 * @returns {Promise<object|null>}
 */
export async function getAdminShareLinkDetail({ videoId }) {
  const query = new URLSearchParams({ video_id: String(videoId) })
  const body = await get(`${DETAIL_PATH}?${query.toString()}`)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('链接详情加载失败：服务未返回标准数据')
  }
  return body.data ?? null
}

/**
 * 2.3 撤回当前生效链接（status 置 0），撤回后用户无法再通过它访问。
 * @param {{ videoId: number|string }} params
 */
export async function revokeAdminShareLink({ videoId }) {
  const body = await post(REVOKE_PATH, { video_id: videoId })

  if (!body || typeof body !== 'object' || body.success === false) {
    throw new Error('链接撤回失败：服务未返回成功结果')
  }
  return true
}
