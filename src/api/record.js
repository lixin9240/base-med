import { get } from './http'

const LIST_PATH = '/api/admin/exam-record/list'
const DETAIL_PATH = '/api/admin/exam-record/detail'

/**
 * 答题记录列表（2.5.1）。返回 { list, total, page, pageSize }，list 内字段保持后端命名。
 * videoId 对应「全部视频」筛选，resultStatus 对应「全部结果」筛选（pass / fail）。
 */
export async function listAdminRecords({
  page = 1,
  pageSize = 20,
  videoId,
  resultStatus,
} = {}) {
  const params = new URLSearchParams({
    page: String(page),
    page_size: String(pageSize),
  })
  if (videoId !== undefined && videoId !== null && videoId !== '') {
    params.set('video_id', String(videoId))
  }
  if (resultStatus) params.set('result_status', resultStatus)

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
