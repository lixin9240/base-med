import { get, post } from './http'

// 用户端公开接口（接口文档第 3 章「伏明月-用户端接口」）
const DR_CREATE_PATH = '/api/public/dr/create'
const VIDEO_LIST_PATH = '/api/public/video/list'
const VIDEO_DETAIL_PATH = '/api/public/video/detail'
const QUESTION_LIST_PATH = '/api/public/video/question-list'
const EXAM_SUBMIT_PRE_PATH = '/api/public/exam/submit-pre'
const EXAM_RESULT_PATH = '/api/public/exam/result'

/** 3.1 获取匿名 DR 编号（token 来自分享链接 ?token=）。返回 dr_id 字符串 */
export async function createPublicDr(token) {
  const body = await get(`${DR_CREATE_PATH}?token=${encodeURIComponent(token)}`)

  const drId = body?.data?.dr_id
  if (!drId) throw new Error('获取匿名编号失败：服务未返回 dr_id')
  return String(drId)
}

/** 3.2 用户端首页视频列表。返回后端原始字段数组 */
export async function listPublicVideos(drId) {
  const body = await get(`${VIDEO_LIST_PATH}?dr_id=${encodeURIComponent(drId)}`)

  if (!body || typeof body !== 'object' || !Array.isArray(body.data)) {
    throw new Error('视频列表加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.3 视频详情。返回后端原始字段对象 */
export async function getPublicVideoDetail(drId, videoId) {
  const body = await get(
    `${VIDEO_DETAIL_PATH}?dr_id=${encodeURIComponent(drId)}&video_id=${encodeURIComponent(videoId)}`,
  )

  if (!body || typeof body !== 'object' || !body.data) {
    throw new Error('视频详情加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.5 获取视频题目列表（学前/看完答题共用）。返回后端原始字段数组，不含答案与解析 */
export async function listPublicQuestions(videoId) {
  const body = await get(`${QUESTION_LIST_PATH}?video_id=${encodeURIComponent(videoId)}`)

  if (!body || typeof body !== 'object' || !Array.isArray(body.data)) {
    throw new Error('题目加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.4 提交学前答题。payload：{ dr_id, video_id, answers: [{ question_id, user_answer }] } */
export async function submitPreExam(payload) {
  const body = await post(EXAM_SUBMIT_PRE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('提交失败：服务未返回标准数据')
  }
  return body.data ?? {}
}

/** 3.6 获取答题结果。params：{ dr_id, video_id? , exam_record_id? }（video_id 与 exam_record_id 二选一） */
export async function getExamResult(params) {
  const query = Object.entries(params)
    .filter(([, value]) => value !== null && value !== undefined && value !== '')
    .map(([key, value]) => `${key}=${encodeURIComponent(value)}`)
    .join('&')

  const body = await get(`${EXAM_RESULT_PATH}?${query}`)

  if (!body || typeof body !== 'object' || !body.data) {
    throw new Error('答题结果加载失败：服务未返回标准数据')
  }
  return body.data
}
