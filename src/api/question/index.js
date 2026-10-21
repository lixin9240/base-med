import { get, post, requestBlob } from '../http'

// 题目配置模块接口（接口文档 2.3）
// 说明：前后测共用一套题，接口不再区分 stage。

const LIST_PATH = '/api/admin/video-question/list'
const ADD_PATH = '/api/admin/video-question/add-question'
const UPDATE_PATH = '/api/admin/video-question/update-question'
const REMOVE_PATH = '/api/admin/video-question/remove'
const TEMPLATE_PATH = '/api/admin/video-question/download-template'
const EXPORT_PATH = '/api/admin/video-question/export'
const IMPORT_PATH = '/api/admin/video/import-question'
const TASK_DETAIL_PATH = '/api/admin/import/task-detail'
const TASK_CONFIRM_PATH = '/api/admin/import/confirm'

/**
 * 2.3.1 获取题目列表（前后测共用一套）。
 * @param {{ videoId: number|string }} params
 * @returns {Promise<{ list: Array, total: number }>} list 内字段保持后端命名（question_id、correct_answer、sort_order 等）
 */
export async function listAdminVideoQuestions({ videoId }) {
  const body = await get(`${LIST_PATH}?video_id=${encodeURIComponent(videoId)}`)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('题目列表加载失败：服务未返回标准数据')
  }

  const data = body.data ?? {}
  return {
    list: Array.isArray(data.list) ? data.list : [],
    total: Number(data.total) || 0,
  }
}

/** 2.3.2 新增题目。payload 用后端字段名（video_id、type、stem、options、correct_answer、analysis）。返回 { id } */
export async function createAdminVideoQuestion(payload) {
  const body = await post(ADD_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('题目创建失败：服务未返回标准数据')
  }
  return body.data
}

/** 2.3.3 编辑题目。payload 同 2.3.2，额外带 question_id */
export async function updateAdminVideoQuestion(payload) {
  const body = await post(UPDATE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('题目保存失败：服务未返回标准数据')
  }
  return body.data ?? null
}

/** 2.3.7 移除题目。payload = { video_id, question_id }；软删除关联，不删题库 */
export async function removeAdminVideoQuestion(payload) {
  const body = await post(REMOVE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('题目删除失败：服务未返回标准数据')
  }
  return body.data ?? null
}

/** 2.3.4 下载题目模板。返回 Excel Blob */
export function downloadQuestionTemplate() {
  return requestBlob(TEMPLATE_PATH)
}

/** 2.3.6 导出题目。返回 Excel Blob */
export function exportAdminVideoQuestions({ videoId }) {
  return requestBlob(`${EXPORT_PATH}?video_id=${encodeURIComponent(videoId)}`)
}

/**
 * 2.3.5 导入题目（form-data 上传 Excel）。
 * 上传后创建异步校验任务，返回 { task_id, task_no, status }。
 */
export async function importAdminVideoQuestions({ videoId, file }) {
  const form = new FormData()
  form.append('video_id', videoId)
  form.append('file', file)

  const body = await post(IMPORT_PATH, form)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data?.task_id) {
    throw new Error('题目导入失败：服务未返回任务信息')
  }
  return body.data
}

/** 2.10.2 导入任务详情（校验结果与错误报告） */
export async function getAdminImportTaskDetail({ taskId }) {
  const body = await get(`${TASK_DETAIL_PATH}?task_id=${encodeURIComponent(taskId)}`)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('导入任务查询失败：服务未返回标准数据')
  }
  return body.data
}

/** 2.10.3 确认导入。仅状态=2（待确认）可执行 */
export async function confirmAdminImportTask({ taskId }) {
  const body = await post(TASK_CONFIRM_PATH, { task_id: taskId })

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('确认导入失败：服务未返回标准数据')
  }
  return body.data ?? null
}
