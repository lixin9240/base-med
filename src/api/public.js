import { get, post } from './http'

// 用户端公开接口（接口文档第 3 章「伏明月-用户端接口」）
const DR_CREATE_PATH = '/api/public/dr/create'
const VIDEO_LIST_PATH = '/api/public/video/list'
const VIDEO_DETAIL_PATH = '/api/public/video/detail'
const VIDEO_PROGRESS_PATH = '/api/public/video/progress'
const QUESTION_LIST_PATH = '/api/public/video/question-list'
const EXAM_SUBMIT_PRE_PATH = '/api/public/exam/submit-pre'
const EXAM_SUBMIT_POST_PATH = '/api/public/exam/submit-post'
const EXAM_RETAKE_PATH = '/api/public/exam/retake'
const EXAM_RESULT_PATH = '/api/public/exam/result'

/** 3.1 获取匿名 DR 编号（点击视频时签发）。参数 video_id。返回 dr_id 字符串 */
export async function createPublicDr(videoId) {
  const body = await get(`${DR_CREATE_PATH}?video_id=${encodeURIComponent(videoId)}`)
  const drId = body?.data?.dr_id
  if (!drId) throw new Error('获取匿名编号失败：服务未返回 dr_id')
  return String(drId)
}

/** 3.2 用户端首页视频列表（免登录、无需 dr_id）。返回后端原始字段数组 */
export async function listPublicVideos() {
  const body = await get(VIDEO_LIST_PATH)
  if (!body || typeof body !== 'object' || !Array.isArray(body.data)) {
    throw new Error('视频列表加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.3 视频详情。返回后端原始字段对象（含 watch_progress） */
export async function getPublicVideoDetail(drId, videoId) {
  const body = await get(
    `${VIDEO_DETAIL_PATH}?dr_id=${encodeURIComponent(drId)}&video_id=${encodeURIComponent(videoId)}`,
  )
  if (!body || typeof body !== 'object' || !body.data) {
    throw new Error('视频详情加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.4 上报视频观看进度。payload：{ dr_id, video_id, watched_seconds, duration? } */
export async function reportVideoProgress(payload) {
  const body = await post(VIDEO_PROGRESS_PATH, payload)
  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('进度上报失败：服务未返回标准数据')
  }
  return body.data ?? {}
}

/** 3.6 获取视频题目列表（学前/看完答题共用）。返回后端原始字段数组，不含答案与解析 */
export async function listPublicQuestions(videoId) {
  const body = await get(`${QUESTION_LIST_PATH}?video_id=${encodeURIComponent(videoId)}`)
  if (!body || typeof body !== 'object' || !Array.isArray(body.data)) {
    throw new Error('题目加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.5 提交学前答题。payload：{ dr_id, video_id, answers: [{ question_id, user_answer }] } */
export async function submitPreExam(payload) {
  const body = await post(EXAM_SUBMIT_PRE_PATH, payload)
  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('提交失败：服务未返回标准数据')
  }
  return body.data ?? {}
}

/** 3.7 提交看完答题。返回结构与 3.8 一致（含 result_status / accuracy_rate / question_detail） */
export async function submitPostExam(payload) {
  const body = await post(EXAM_SUBMIT_POST_PATH, payload)
  if (!body || typeof body !== 'object' || !body.data) {
    throw new Error('提交失败：服务未返回标准数据')
  }
  return body.data
}

/**
 * 3.9 提交再次答题（仅重评后测，复用最近一次学前结果）。
 * 返回结构与 3.8 一致，可直接跳转结果页。
 */
export async function submitRetakeExam(payload) {
  const body = await post(EXAM_RETAKE_PATH, payload)
  if (!body || typeof body !== 'object' || !body.data) {
    throw new Error('提交失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.8 获取答题结果。params：{ dr_id, video_id? , exam_record_id? }（video_id 与 exam_record_id 二选一） */
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
