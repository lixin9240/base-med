import { post } from '@/api'
import { COVER_MAX_SIZE_MB, VIDEO_MAX_SIZE_MB } from './constants'

const VIDEO_TYPES = /^video\/(mp4|webm|ogg|quicktime)$/i
const IMAGE_TYPES = /^image\/(jpeg|png|webp|gif)$/i

function sizeInMb(file) {
  return file.size / 1024 / 1024
}

/** 校验视频文件，返回错误文案；通过时返回空字符串 */
export function validateVideoFile(file) {
  if (!VIDEO_TYPES.test(file.type)) return '只支持 mp4 / webm / ogg / mov 格式的视频'
  if (sizeInMb(file) > VIDEO_MAX_SIZE_MB) return `视频不能超过 ${VIDEO_MAX_SIZE_MB}MB`
  return ''
}

/** 校验封面图片，返回错误文案；通过时返回空字符串 */
export function validateCoverFile(file) {
  if (!IMAGE_TYPES.test(file.type)) return '只支持 jpg / png / webp / gif 格式的图片'
  if (sizeInMb(file) > COVER_MAX_SIZE_MB) return `图片不能超过 ${COVER_MAX_SIZE_MB}MB`
  return ''
}

/**
 * 上传视频。
 * 未配置 VITE_UPLOAD_ENDPOINT 时返回本地 objectURL，仅供演示预览。
 */
export async function uploadVideoFile(file) {
  const endpoint = import.meta.env.VITE_UPLOAD_ENDPOINT
  if (!endpoint) return URL.createObjectURL(file)

  const form = new FormData()
  form.append('file', file)
  form.append('kind', 'video')
  const result = await post(endpoint, form)
  if (!result?.url) throw new Error('上传失败：服务未返回文件地址')
  return result.url
}

/**
 * 上传封面（POST /api/admin/video/upload/cover）。
 * 返回 OSS 地址；失败抛出异常。
 */
export async function uploadCoverFile(file) {
  const form = new FormData()
  form.append('file', file)
  const body = await post('/api/admin/video/upload/cover', form)
  const url = body?.data?.url
  if (!url) throw new Error('封面上传失败：服务未返回地址')
  return url
}
