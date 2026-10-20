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

function readAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result ?? ''))
    reader.onerror = () => reject(new Error('读取图片失败'))
    reader.readAsDataURL(file)
  })
}

async function uploadFile(file, kind) {
  const endpoint = import.meta.env.VITE_UPLOAD_ENDPOINT
  if (!endpoint) {
    return kind === 'cover' ? readAsDataUrl(file) : URL.createObjectURL(file)
  }

  const form = new FormData()
  form.append('file', file)
  form.append('kind', kind)
  const result = await post(endpoint, form)
  if (!result?.url) throw new Error('上传失败：服务未返回文件地址')
  return result.url
}

/**
 * 上传视频。
 * 未配置 VITE_UPLOAD_ENDPOINT 时返回本地 objectURL，仅供演示预览。
 */
export function uploadVideoFile(file) {
  return uploadFile(file, 'video')
}

/**
 * 上传封面。
 * 未配置 VITE_UPLOAD_ENDPOINT 时转成 dataURL，刷新后仍可显示。
 */
export function uploadCoverFile(file) {
  return uploadFile(file, 'cover')
}
