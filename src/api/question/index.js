import { get, post } from '../http'

// 题目配置模块接口（接口文档 2.3）
// 说明：stage 取值为 pretest（前测）/ posttest（后测），
//       与前端内部使用的 pre / post 不同，转换在组件层完成。

const LIST_PATH = '/api/admin/video-question/list'
const ADD_PATH = '/api/admin/video-question/add-question'
const UPDATE_PATH = '/api/admin/video-question/update-question'

/**
 * 2.3.1 获取前/后测题目列表。
 * @param {{ videoId: number|string, stage: 'pretest' | 'posttest' }} params
 * @returns {Promise<{ list: Array, total: number }>} list 内字段保持后端命名（question_id、correct_answer、sort_order 等）
 */
export async function listAdminVideoQuestions({ videoId, stage }) {
  const body = await get(
    `${LIST_PATH}?video_id=${encodeURIComponent(videoId)}&stage=${encodeURIComponent(stage)}`,
  )

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('题目列表加载失败：服务未返回标准数据')
  }

  const data = body.data ?? {}
  return {
    list: Array.isArray(data.list) ? data.list : [],
    total: Number(data.total) || 0,
  }
}

/** 2.3.2 新增题目。payload 用后端字段名（video_id、stage、type、stem、options、correct_answer、analysis）。返回 { id } */
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
