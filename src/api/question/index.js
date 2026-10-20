import { get } from '../http'

// 题目配置模块接口（接口文档 2.3）
// 说明：stage 取值为 pretest（前测）/ posttest（后测），
//       与前端内部使用的 pre / post 不同，转换在组件层完成。

const LIST_PATH = '/api/admin/video-question/list'

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
