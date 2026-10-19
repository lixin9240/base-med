import { adminVideos } from '@/mock/admin'
import {
  ensureStoreSeed as ensureCoreStoreSeed,
  findCourseByTitle as findCoreCourseByTitle,
  listQuestions as listCoreQuestions,
  replaceQuestions as replaceCoreQuestions,
} from '@/store'

/** 每个视频每套题（前测 / 后测）建议的题目数量 */
export const REQUIRED_PER_STAGE = 3

function resolveCourseId(video) {
  return findCoreCourseByTitle(video.title)?.id ?? `admin_${video.id}`
}

function countBadge(courseId, stage) {
  const count = listCoreQuestions(courseId, stage).length
  return {
    text: `${count}/${REQUIRED_PER_STAGE}`,
    tone: count >= REQUIRED_PER_STAGE ? 'success' : count > 0 ? 'warning' : 'danger',
  }
}

/**
 * 视频列表（管理端），题目数量取自题库。
 */
export function listVideos() {
  ensureCoreStoreSeed()
  return adminVideos.map((video) => {
    const courseId = resolveCourseId(video)
    const pre = countBadge(courseId, 'pre')
    const post = countBadge(courseId, 'post')
    return {
      ...video,
      courseId,
      pre: pre.text,
      preTone: pre.tone,
      post: post.text,
      postTone: post.tone,
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
  const video = adminVideos.find((item) => item.id === videoId)
  if (!video) return null
  return { ...video, courseId: resolveCourseId(video) }
}

export function listVideoQuestions(videoId, stage) {
  ensureCoreStoreSeed()
  const video = adminVideos.find((item) => item.id === videoId)
  if (!video) return []
  return listCoreQuestions(resolveCourseId(video), stage)
}

/**
 * 新增 / 编辑单道题。
 * 传入 question.id 时视为编辑，保留原题序；否则追加到本组末尾。
 */
export function saveVideoQuestion(videoId, stage, question) {
  ensureCoreStoreSeed()
  const video = adminVideos.find((item) => item.id === videoId)
  if (!video) throw new Error('视频不存在')

  const courseId = resolveCourseId(video)
  const group = listCoreQuestions(courseId, stage)
  let next

  if (question.id) {
    const index = group.findIndex((item) => item.id === question.id)
    if (index === -1) throw new Error('题目不存在')

    next = group.map((item, i) =>
      i === index
        ? { ...question, courseId, stage, id: item.id, order: item.order }
        : item,
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
  const video = adminVideos.find((item) => item.id === videoId)
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
    const courseId = group.courseId ?? `admin_${group.videoId}`
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
