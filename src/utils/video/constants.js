/** 视频标题最多字数 */
export const VIDEO_TITLE_MAX = 60

/** 一句话简介最多字数 */
export const VIDEO_INTRO_MAX = 120

/** 视频分类（管理端下拉、用户端标签共用） */
export const VIDEO_CATEGORIES = [
  '急救技能',
  '慢病管理',
  '影像检查',
  '妇幼保健',
  '用药安全',
  '健康科普',
]

/** 视频来源 */
export const VIDEO_SOURCE = {
  URL: 'url',
  UPLOAD: 'upload',
}

/** 分享链接可选时效 */
export const SHARE_TTL_OPTIONS = [
  { days: 1, label: '1 天' },
  { days: 3, label: '3 天' },
  { days: 5, label: '5 天' },
  { days: 7, label: '7 天' },
]

/** 分享链接默认时效（天） */
export const DEFAULT_SHARE_TTL = 1

/** 视频文件大小上限（MB） */
export const VIDEO_MAX_SIZE_MB = 500

/** 封面图片大小上限（MB） */
export const COVER_MAX_SIZE_MB = 2

/** 题目阶段：前端内部值 ↔ 接口值 */
export const STAGE_TO_API = { pre: 'pretest', post: 'posttest' }
export const STAGE_FROM_API = { pretest: 'pre', posttest: 'post' }
