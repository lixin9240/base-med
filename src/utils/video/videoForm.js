import { VIDEO_CATEGORIES, VIDEO_INTRO_MAX, VIDEO_SOURCE, VIDEO_TITLE_MAX } from './constants'

/** 新建 / 编辑弹窗的初始草稿 */
export function createVideoDraft(video) {
  return {
    id: video?.id ?? '',
    courseId: video?.courseId ?? '',
    title: video?.title ?? '',
    category: video?.category || VIDEO_CATEGORIES[0],
    speaker: video?.speaker ?? '',
    speakerTitle: video?.speakerTitle ?? '',
    speakerOrg: video?.speakerOrg ?? '',
    intro: video?.intro ?? '',
    source: VIDEO_SOURCE.URL,
    videoUrl: video?.videoUrl ?? '',
    cover: video?.cover ?? '',
    articleBody: video?.articleBody ?? '',
    published: video?.published ?? true,
    order: video?.order ?? 1,
  }
}

function isPlayableUrl(value) {
  const url = String(value ?? '').trim()
  return /^https?:\/\//i.test(url) || url.startsWith('/') || url.startsWith('blob:')
}

/**
 * 校验视频草稿。
 * @returns {{ valid: boolean, errors: Record<string, string> }}
 */
export function validateVideoDraft(draft) {
  const errors = {}

  const title = String(draft.title ?? '').trim()
  if (!title) errors.title = '请填写视频标题'
  else if (title.length > VIDEO_TITLE_MAX) errors.title = `标题不能超过 ${VIDEO_TITLE_MAX} 个字`

  if (!draft.category) errors.category = '请选择分类'

  if (!String(draft.speaker ?? '').trim()) errors.speaker = '请填写主讲人'

  const intro = String(draft.intro ?? '').trim()
  if (!intro) errors.intro = '请填写一句话简介'
  else if (intro.length > VIDEO_INTRO_MAX) errors.intro = `简介不能超过 ${VIDEO_INTRO_MAX} 个字`

  const videoUrl = String(draft.videoUrl ?? '').trim()
  if (!videoUrl) {
    errors.videoUrl = draft.source === VIDEO_SOURCE.UPLOAD ? '请上传视频文件' : '请填写视频链接'
  } else if (draft.source === VIDEO_SOURCE.URL && !isPlayableUrl(videoUrl)) {
    errors.videoUrl = '链接需以 http:// 或 https:// 开头'
  }

  const order = Number(draft.order)
  if (!Number.isInteger(order) || order < 1) errors.order = '排序号需为正整数'

  return { valid: Object.keys(errors).length === 0, errors }
}

/** 视频记录 -> 用户端课程记录 */
export function toCourse(video) {
  return {
    id: video.courseId,
    title: String(video.title ?? '').trim(),
    category: video.category,
    cover: video.cover || '',
    speaker: String(video.speaker ?? '').trim(),
    speakerOrg: String(video.speakerOrg ?? '').trim(),
    speakerTitle: String(video.speakerTitle ?? '').trim(),
    intro: String(video.intro ?? '').trim(),
    videoUrl: String(video.videoUrl ?? '').trim(),
    articleBody: video.articleBody || '',
    status: video.published ? 'published' : 'offline',
    order: Number(video.order) || 1,
    createdAt: video.createdAt ?? Date.now(),
  }
}

/** 列表里展示的主讲人文案 */
export function videoSpeakerLabel(video) {
  return [video.speaker, video.speakerTitle].filter(Boolean).join(' · ') || '未指定'
}
