import { adminVideos } from '@/mock/admin'
import {
  ensureStoreSeed as ensureCoreStoreSeed,
  findCourseByTitle as findCoreCourseByTitle,
  getCourse,
  listQuestions as listCoreQuestions,
  newId,
  removeCourse,
  replaceQuestions as replaceCoreQuestions,
  saveCourse,
} from '@/store'
import { toCourse, videoSpeakerLabel } from '@/utils/video/videoForm'
import { clearShareLink } from './shareLink'

/** 每个视频建议的题目数量（前后测共用一套） */
export const REQUIRED_QUESTION_COUNT = 3

/** 管理端视频记录的持久化 key */
const VIDEOS_KEY = 'pv.adminVideos'

function readVideos() {
  try {
    const raw = localStorage.getItem(VIDEOS_KEY)
    if (raw === null) return null
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed : []
  } catch (error) {
    console.error('[video] read failed', error)
    return []
  }
}

function writeVideos(videos) {
  try {
    localStorage.setItem(VIDEOS_KEY, JSON.stringify(videos))
    return true
  } catch (error) {
    console.error('[video] write failed', error)
    return false
  }
}

function normalizeSeedVideo(video, index) {
  const course = findCoreCourseByTitle(video.title)
  const [speakerName, speakerTitle] = String(video.speaker ?? '').split(' · ')

  return {
    id: video.id,
    courseId: course?.id ?? `admin_${video.id}`,
    title: video.title,
    category: video.category,
    speaker: course?.speaker || speakerName || '',
    speakerTitle: course?.speakerTitle || speakerTitle || '',
    speakerOrg: course?.speakerOrg || '',
    intro: course?.intro || '',
    videoUrl: course?.videoUrl || '',
    cover: course?.cover || '',
    articleBody: course?.articleBody || '',
    published: Boolean(video.published),
    order: course?.order ?? index + 1,
    createdAt: course?.createdAt ?? Date.now(),
    attempts: video.attempts ?? '0 次',
    accuracy: video.accuracy ?? '',
    accuracyTone: video.accuracyTone ?? '',
  }
}

/** 题库版本升级后，把管理端新增/改过的视频课程补回 pv.courses */
function ensureCourses(videos) {
  for (const video of videos) {
    if (!video.courseId || getCourse(video.courseId)) continue
    saveCourse(toCourse(video))
  }
}

/**
 * 首次使用 adminVideos mock 初始化 pv.adminVideos，之后以 localStorage 为准。
 */
function seedVideos() {
  ensureCoreStoreSeed()
  const stored = readVideos()
  if (stored !== null) {
    ensureCourses(stored)
    return stored
  }

  const seeded = adminVideos.map(normalizeSeedVideo)
  writeVideos(seeded)
  return seeded
}

function resolveCourseId(video) {
  return video.courseId ?? findCoreCourseByTitle(video.title)?.id ?? `admin_${video.id}`
}

function findRecord(videoId) {
  return seedVideos().find((video) => String(video.id) === String(videoId)) ?? null
}

function countBadge(courseId) {
  // 前后测共用一套，mock 数据统一按 pre 组统计
  const count = listCoreQuestions(courseId, 'pre').length
  return {
    count,
    text: `${count}/${REQUIRED_QUESTION_COUNT}`,
    tone: count >= REQUIRED_QUESTION_COUNT ? 'success' : count > 0 ? 'warning' : 'danger',
  }
}

function buildRecord(draft, base = {}) {
  return {
    id: base.id ?? draft.id ?? newId('v_'),
    courseId: base.courseId ?? draft.courseId ?? newId('c_'),
    title: String(draft.title ?? '').trim(),
    category: draft.category,
    speaker: String(draft.speaker ?? '').trim(),
    speakerTitle: String(draft.speakerTitle ?? '').trim(),
    speakerOrg: String(draft.speakerOrg ?? '').trim(),
    intro: String(draft.intro ?? '').trim(),
    videoUrl: String(draft.videoUrl ?? '').trim(),
    cover: draft.cover || '',
    articleBody: draft.articleBody || '',
    published: Boolean(draft.published),
    order: Number(draft.order) || 1,
    createdAt: base.createdAt ?? draft.createdAt ?? Date.now(),
    updatedAt: Date.now(),
    attempts: base.attempts ?? draft.attempts ?? '0 次',
    accuracy: base.accuracy ?? draft.accuracy ?? '',
    accuracyTone: base.accuracyTone ?? draft.accuracyTone ?? '',
  }
}

/**
 * 视频列表（管理端），题目数量取自题库。
 */
export function listVideos() {
  ensureCoreStoreSeed()
  return seedVideos()
    .slice()
    .sort((a, b) => (Number(a.order) || 999) - (Number(b.order) || 999))
    .map((video) => {
      const courseId = resolveCourseId(video)
      const badge = countBadge(courseId)
      return {
        ...video,
        courseId,
        speakerLabel: videoSpeakerLabel(video),
        questionCount: badge.count,
        question: badge.text,
        questionTone: badge.tone,
      }
    })
}

export function findVideoByTitle(title) {
  const target = String(title ?? '').trim()
  if (!target) return null
  return listVideos().find((video) => String(video.title).trim() === target) ?? null
}

export function getVideo(videoId) {
  ensureCoreStoreSeed()
  const video = findRecord(videoId)
  if (!video) return null
  return { ...video, courseId: resolveCourseId(video) }
}

/**
 * 新增视频：写管理端列表，同时同步一条用户端课程。
 */
export function createVideo(draft) {
  ensureCoreStoreSeed()
  const videos = seedVideos()
  const record = buildRecord(draft, {
    id: newId('v_'),
    courseId: newId('c_'),
    attempts: '0 次',
    accuracy: '',
    accuracyTone: '',
  })

  videos.push(record)
  writeVideos(videos)
  saveCourse(toCourse(record))
  return record
}

/**
 * 编辑视频：保留题目统计，同步更新用户端课程。
 */
export function updateVideo(videoId, draft) {
  ensureCoreStoreSeed()
  const videos = seedVideos()
  const index = videos.findIndex((video) => String(video.id) === String(videoId))
  if (index === -1) throw new Error('视频不存在')

  const record = buildRecord(draft, videos[index])
  videos[index] = record
  writeVideos(videos)
  saveCourse(toCourse(record))
  return record
}

/** 上架 / 下架 */
export function setVideoPublished(videoId, published) {
  const video = findRecord(videoId)
  if (!video) throw new Error('视频不存在')

  const videos = seedVideos()
  const index = videos.findIndex((item) => String(item.id) === String(videoId))
  videos[index] = { ...video, published: Boolean(published), updatedAt: Date.now() }
  writeVideos(videos)
  saveCourse(toCourse(videos[index]))
  return videos[index]
}

/** 删除视频：同时清理课程、题目和分享链接 */
export function removeVideo(videoId) {
  ensureCoreStoreSeed()
  const videos = seedVideos()
  const index = videos.findIndex((video) => String(video.id) === String(videoId))
  if (index === -1) return false

  const [video] = videos.splice(index, 1)
  writeVideos(videos)

  const courseId = resolveCourseId(video)
  replaceCoreQuestions(courseId, 'pre', [])
  replaceCoreQuestions(courseId, 'post', [])
  removeCourse(courseId)
  clearShareLink(video.id)
  return true
}

export function listVideoQuestions(videoId, stage) {
  ensureCoreStoreSeed()
  const video = findRecord(videoId)
  if (!video) return []
  return listCoreQuestions(resolveCourseId(video), stage)
}

/**
 * 新增 / 编辑单道题。
 * 传入 question.id 时视为编辑，保留原题序；否则追加到本组末尾。
 */
export function saveVideoQuestion(videoId, stage, question) {
  ensureCoreStoreSeed()
  const video = findRecord(videoId)
  if (!video) throw new Error('视频不存在')

  const courseId = resolveCourseId(video)
  const group = listCoreQuestions(courseId, stage)
  let next

  if (question.id) {
    const index = group.findIndex((item) => item.id === question.id)
    if (index === -1) throw new Error('题目不存在')

    next = group.map((item, i) =>
      i === index ? { ...question, courseId, stage, id: item.id, order: item.order } : item,
    )
  } else {
    const maxOrder = group.reduce((max, item) => Math.max(max, Number(item.order) || 0), 0)
    const order = maxOrder + 1
    next = group.concat({
      ...question,
      courseId,
      stage,
      order,
      id: `q_${courseId}_${stage}_${order}`,
    })
  }

  next.sort((a, b) => (a.order ?? 999) - (b.order ?? 999))
  replaceCoreQuestions(courseId, stage, next)
  return next
}

/**
 * 删除单道题。保留剩余题目题序，避免改变既有 questionId。
 */
export function removeVideoQuestion(videoId, stage, questionId) {
  ensureCoreStoreSeed()
  const video = findRecord(videoId)
  if (!video) return []

  const courseId = resolveCourseId(video)
  const group = listCoreQuestions(courseId, stage)
  const next = group.filter((item) => item.id !== questionId)
  if (next.length === group.length) return group

  replaceCoreQuestions(courseId, stage, next)
  return next
}

/**
 * 批量导入：按「视频 + 用途」整组替换题目。
 *
 * @param {Array<{ videoId: number, courseId?: string, stage: string, questions: Array }>} groups
 * @returns {{ imported: number }}
 */
export function importVideoQuestions(groups) {
  ensureCoreStoreSeed()
  let imported = 0

  for (const group of groups) {
    const video = findRecord(group.videoId)
    const courseId = group.courseId ?? (video ? resolveCourseId(video) : `admin_${group.videoId}`)
    const questions = group.questions.map((question) => ({
      ...question,
      courseId,
      stage: group.stage,
      id: `q_${courseId}_${group.stage}_${question.order}`,
    }))

    replaceCoreQuestions(courseId, group.stage, questions)
    imported += questions.length
  }

  return { imported }
}
