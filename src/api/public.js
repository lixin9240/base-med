import { USE_MOCK } from '@/config'
import { get, post } from './http'
import { optionLetter } from '@/utils/question'
import {
  PASS_PERCENT,
  ensureLearner,
  getCourse,
  getProgress,
  getRecord,
  listPublishedCourses,
  listQuestions,
  newId,
  saveProgress,
  saveRecord,
} from '@/store'

// 用户端公开接口（接口文档第 3 章「伏明月-用户端接口」）
const DR_CREATE_PATH = '/api/public/dr/create'
const VIDEO_LIST_PATH = '/api/public/video/list'
const VIDEO_DETAIL_PATH = '/api/public/video/detail'
const QUESTION_LIST_PATH = '/api/public/video/question-list'
const EXAM_SUBMIT_PRE_PATH = '/api/public/exam/submit-pre'
const EXAM_RESULT_PATH = '/api/public/exam/result'

/** 3.1 获取匿名 DR 编号（token 来自分享链接 ?token=）。返回 dr_id 字符串 */
export async function createPublicDr(token) {
  if (USE_MOCK) return mockCreateDr()

  const body = await get(`${DR_CREATE_PATH}?token=${encodeURIComponent(token)}`)
  const drId = body?.data?.dr_id
  if (!drId) throw new Error('获取匿名编号失败：服务未返回 dr_id')
  return String(drId)
}

/** 3.2 用户端首页视频列表。返回后端原始字段数组 */
export async function listPublicVideos(drId) {
  if (USE_MOCK) return mockListVideos(drId)

  const body = await get(`${VIDEO_LIST_PATH}?dr_id=${encodeURIComponent(drId)}`)
  if (!body || typeof body !== 'object' || !Array.isArray(body.data)) {
    throw new Error('视频列表加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.3 视频详情。返回后端原始字段对象 */
export async function getPublicVideoDetail(drId, videoId) {
  if (USE_MOCK) return mockVideoDetail(drId, videoId)

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
  if (USE_MOCK) return mockQuestions(videoId)

  const body = await get(`${QUESTION_LIST_PATH}?video_id=${encodeURIComponent(videoId)}`)
  if (!body || typeof body !== 'object' || !Array.isArray(body.data)) {
    throw new Error('题目加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 3.4 提交学前答题。payload：{ dr_id, video_id, answers: [{ question_id, user_answer }] } */
export async function submitPreExam(payload) {
  if (USE_MOCK) return mockSubmitPre(payload)

  const body = await post(EXAM_SUBMIT_PRE_PATH, payload)
  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('提交失败：服务未返回标准数据')
  }
  return body.data ?? {}
}

/**
 * 提交看完答题。接口文档未定义该接口（submit-post），真实模式下不可用；
 * mock 模式下本地判分并落记录，返回 exam_record_id。
 */
export async function submitPostExam(payload) {
  if (USE_MOCK) return mockSubmitPost(payload)
  throw new Error('提交看完答题接口尚未定义')
}

/** 3.6 获取答题结果。params：{ dr_id, video_id? , exam_record_id? }（video_id 与 exam_record_id 二选一） */
export async function getExamResult(params) {
  if (USE_MOCK) return mockResult(params)

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

/* ------------------------------- mock 实现 ------------------------------- */

function mockCreateDr() {
  // 本地签发匿名编号（沿用旧的 DR-2026-XXXXXX 规则）
  return ensureLearner().userId
}

function mockListVideos(drId) {
  return listPublishedCourses().map((course) => {
    const p = getProgress(drId, course.id)
    return {
      id: course.id,
      title: course.title,
      category_name: course.category,
      speaker: course.speaker,
      brief_intro: course.intro,
      cover_url: course.cover,
      exam_status: p.postDone ? 'done' : p.preDone ? 'pre' : 'none',
    }
  })
}

function mockVideoDetail(drId, videoId) {
  const course = getCourse(videoId)
  if (!course) throw new Error('视频不存在或已下架')
  const p = getProgress(drId, videoId)

  return {
    id: course.id,
    title: course.title,
    category_name: course.category,
    speaker: course.speaker,
    speaker_title: course.speakerTitle,
    speaker_org: course.speakerOrg,
    brief_intro: course.intro,
    text_key_points: course.articleBody,
    video_url: course.videoUrl,
    cover_url: course.cover,
    duration: 0,
    exam_state: {
      pre_submitted: p.preDone ? 1 : 0,
      post_submitted: p.postDone ? 1 : 0,
      exam_record_id: p.lastRecordId || null,
    },
  }
}

function mockQuestions(videoId) {
  // 新接口前测/后测同一套题，mock 统一取 pre 组
  return listQuestions(videoId, 'pre').map((q) => ({
    question_id: q.id,
    type: q.type,
    stem: q.stem,
    image_url: '',
    options: toApiOptions(q.options),
  }))
}

function mockSubmitPre({ dr_id, video_id, answers }) {
  const questions = listQuestions(video_id, 'pre')
  const userAnswers = (answers || []).map((a) => a?.user_answer ?? '')
  const p = getProgress(dr_id, video_id)
  saveProgress(dr_id, video_id, { ...p, preDone: true, preAnswers: userAnswers })
  return { submitted: 1, pre_total: questions.length, pre_answered: userAnswers.length }
}

function mockSubmitPost({ dr_id, video_id, answers }) {
  const userAnswers = (answers || []).map((a) => a?.user_answer ?? '')
  const p = getProgress(dr_id, video_id)
  const recordId = newId('r_')
  saveProgress(dr_id, video_id, { ...p, postDone: true, postAnswers: userAnswers, lastRecordId: recordId })
  saveRecord({ id: recordId, videoId: video_id, courseId: video_id })
  return { exam_record_id: recordId, video_id }
}

function mockResult(params) {
  let videoId = params.video_id
  if (!videoId && params.exam_record_id) {
    const record = getRecord(params.exam_record_id)
    videoId = record?.videoId ?? record?.courseId
  }
  if (!videoId) throw new Error('答题结果加载失败')

  const course = getCourse(videoId)
  if (!course) throw new Error('视频不存在')

  const questions = listQuestions(videoId, 'pre')
  const p = getProgress(params.dr_id, videoId)
  const preAnswers = p.preAnswers || []
  const postAnswers = p.postAnswers || []

  let preCorrect = 0
  let postCorrect = 0
  let improvedCount = 0

  const questionDetail = questions.map((q, i) => {
    const preAnswer = preAnswers[i] ?? ''
    const postAnswer = postAnswers[i] ?? ''
    const preOk = isCorrect(q, preAnswer)
    const postOk = isCorrect(q, postAnswer)
    if (preOk) preCorrect += 1
    if (postOk) postCorrect += 1
    if (!preOk && postOk) improvedCount += 1

    return {
      question_id: q.id,
      type: q.type,
      stem: q.stem,
      options: toApiOptions(q.options),
      correct_answer: toApiCorrectAnswer(q),
      analysis: q.analysis,
      pre_user_answer: preAnswer,
      pre_is_correct: preOk ? 1 : 0,
      post_user_answer: postAnswer,
      post_is_correct: postOk ? 1 : 0,
      improved: !preOk && postOk ? 1 : 0,
    }
  })

  const total = questions.length
  const postPercent = total ? Math.round((postCorrect / total) * 100) : 0

  return {
    exam_record_id: p.lastRecordId || null,
    video_id: videoId,
    video_title: course.title,
    result_status: postPercent >= PASS_PERCENT ? 'pass' : 'fail',
    pass_accuracy: PASS_PERCENT,
    accuracy_rate: postPercent,
    improved_count: improvedCount,
    pre: { correct: preCorrect, total, accuracy_rate: total ? Math.round((preCorrect / total) * 100) : 0 },
    post: { correct: postCorrect, total, accuracy_rate: postPercent },
    question_detail: questionDetail,
  }
}

/** 旧数据选项（字符串数组）→ 接口选项结构 [{ key, content }] */
function toApiOptions(options) {
  return (options || []).map((content, i) => ({ key: optionLetter(i), content }))
}

/** 旧数据正确答案 → 接口正确字段（判断题转「正确/错误」，多选转 "A,B"） */
function toApiCorrectAnswer(question) {
  if (question.type === 'judge') return question.answer === 'A' ? '正确' : '错误'
  if (question.type === 'multiple') {
    return (Array.isArray(question.answer) ? question.answer : []).sort().join(',')
  }
  return question.answer
}

/** 判分：接口格式的 user_answer 与旧数据正确答案比较 */
function isCorrect(question, userAnswer) {
  const expected = toApiCorrectAnswer(question)
  if (question.type === 'multiple') {
    const a = String(userAnswer || '')
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
      .sort()
    const b = String(expected || '')
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
      .sort()
    return a.length > 0 && a.length === b.length && a.every((v, i) => v === b[i])
  }
  return String(userAnswer || '') === String(expected)
}
