import { get } from './http'

const LIST_PATH = '/api/admin/exam-record/list'
const DETAIL_PATH = '/api/admin/exam-record/detail'
const EXPORT_PATH = '/api/admin/exam-record/export'

/**
 * 答题记录列表（2.5.1）。返回 { list, total, page, pageSize }，list 内字段保持后端命名。
 * videoId / resultStatus / isMedicalStaff 对应筛选；isMedicalStaff：1 医护、0 非医护（含未采集）、空=全部。
 */
export async function listAdminRecords({
  page = 1,
  pageSize = 20,
  videoId,
  resultStatus,
  isMedicalStaff,
} = {}) {
  const params = new URLSearchParams({
    page: String(page),
    page_size: String(pageSize),
  })
  if (videoId !== undefined && videoId !== null && videoId !== '') {
    params.set('video_id', String(videoId))
  }
  if (resultStatus) params.set('result_status', resultStatus)
  if (isMedicalStaff === 0 || isMedicalStaff === 1 || isMedicalStaff === '0' || isMedicalStaff === '1') {
    params.set('is_medical_staff', String(isMedicalStaff))
  }

  const body = await get(`${LIST_PATH}?${params.toString()}`)

  // 后端未就绪时可能拿到 HTML 等非信封响应，这种情况必须报错，不能当成「暂无记录」
  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('答题记录加载失败：服务未返回标准数据')
  }

  const data = body.data ?? {}
  return {
    list: Array.isArray(data.list) ? data.list : [],
    total: Number(data.total) || 0,
    page: Number(data.page) || page,
    pageSize: Number(data.page_size) || pageSize,
  }
}

/** 作答详情（2.5.2）：头部小结 + 逐题列表，题目按前测、后测顺序排列 */
export async function getAdminRecordDetail(examRecordId) {
  const body = await get(`${DETAIL_PATH}?exam_record_id=${examRecordId}`)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('作答详情加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 导出答题记录（2.5.3）：后端生成 xlsx 并返回带签名的临时下载地址，返回 { filename, url } */
export async function exportAdminRecords({ videoId, resultStatus, isMedicalStaff } = {}) {
  const params = new URLSearchParams()
  if (videoId !== undefined && videoId !== null && videoId !== '') {
    params.set('video_id', String(videoId))
  }
  if (resultStatus) params.set('result_status', resultStatus)
  if (isMedicalStaff === 0 || isMedicalStaff === 1 || isMedicalStaff === '0' || isMedicalStaff === '1') {
    params.set('is_medical_staff', String(isMedicalStaff))
  }

  const query = params.toString()
  const body = await get(query ? `${EXPORT_PATH}?${query}` : EXPORT_PATH)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data?.url) {
    throw new Error('导出失败：服务未返回下载地址')
  }
  return body.data
}
