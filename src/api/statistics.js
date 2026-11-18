import { get, requestDownload } from './http'

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

/** 拼医护筛选查询串；统计页始终传 0/1 */
function medicalStaffQuery(isMedicalStaff) {
  if (isMedicalStaff === 0 || isMedicalStaff === 1 || isMedicalStaff === '0' || isMedicalStaff === '1') {
    return `is_medical_staff=${encodeURIComponent(isMedicalStaff)}`
  }
  return ''
}

function withQuery(path, isMedicalStaff) {
  const query = medicalStaffQuery(isMedicalStaff)
  return query ? `${path}?${query}` : path
}

/** 统计大盘（2.6.1）：指标卡；isMedicalStaff：1 有医护 / 0 无医护 */
export async function getAdminStatisticsOverview(isMedicalStaff) {
  const body = await get(withQuery(OVERVIEW_PATH, isMedicalStaff))

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('统计大盘加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 各视频答题情况（2.6.2）：全量数组，无分页 */
export async function listAdminStatisticsVideos(isMedicalStaff) {
  const body = await get(withQuery(VIDEO_LIST_PATH, isMedicalStaff))
  return unwrapList(body, '各视频答题情况加载失败：服务未返回标准数据')
}

/** 最容易答错的题（2.6.3）：按错误率从高到低 */
export async function listAdminStatisticsWrongQuestions(isMedicalStaff) {
  const body = await get(withQuery(WRONG_QUESTION_PATH, isMedicalStaff))
  return unwrapList(body, '最容易答错的题加载失败：服务未返回标准数据')
}

/**
 * 导出统计（2.6.4）：返回 Excel 文件流，跟随当前医护筛选。
 * @returns {Promise<{ blob: Blob, filename: string }>}
 */
export async function exportAdminStatistics(isMedicalStaff) {
  return requestDownload(withQuery(EXPORT_PATH, isMedicalStaff))
}
