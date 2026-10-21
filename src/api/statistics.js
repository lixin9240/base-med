import { get, requestBlob } from './http'

const OVERVIEW_PATH = '/api/admin/statistics/overview'
const VIDEO_LIST_PATH = '/api/admin/statistics/video-list'
const WRONG_QUESTION_PATH = '/api/admin/statistics/wrong-question'
const EXPORT_PATH = '/api/admin/statistics/export'

/** 数组型响应：兼容 { code, data: [...] } 信封与直接返回数组两种形式 */
function unwrapList(body, message) {
  if (!body || typeof body !== 'object') throw new Error(message)
  const raw = Array.isArray(body) ? body : body.data
  if (!Array.isArray(raw)) throw new Error(message)
  return raw
}

/** 统计大盘（2.6.1）：4 张指标卡数据 */
export async function getAdminStatisticsOverview() {
  const body = await get(OVERVIEW_PATH)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('统计大盘加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 各视频答题情况（2.6.2）：全量数组，无分页 */
export async function listAdminStatisticsVideos() {
  const body = await get(VIDEO_LIST_PATH)
  return unwrapList(body, '各视频答题情况加载失败：服务未返回标准数据')
}

/** 最容易答错的题（2.6.3）：按错误率从高到低 */
export async function listAdminStatisticsWrongQuestions() {
  const body = await get(WRONG_QUESTION_PATH)
  return unwrapList(body, '最容易答错的题加载失败：服务未返回标准数据')
}

/** 导出统计（2.6.4）：返回 Excel 文件流（Blob） */
export async function exportAdminStatistics() {
  return requestBlob(EXPORT_PATH)
}
