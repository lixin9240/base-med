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

export function listVideoQuestions(videoId, stage) {
  ensureCoreStoreSeed()
  const video = adminVideos.find((item) => item.id === videoId)
  if (!video) return []
  return listCoreQuestions(resolveCourseId(video), stage)
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
