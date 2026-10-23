import {
  completeAdminVideoUpload,
  initAdminVideoUpload,
  uploadAdminVideoChunk,
} from '@/api'
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
 * 本地上传视频（2.3.7 初始化 → 2.3.2 分片上传 → 2.3.8 完成上传）。
 * 完成后后端会把 OSS 地址回写到 video.video_url，并在返回体里带 video_url。
 *
 * @param {File} file 视频文件
 * @param {{ videoId: number, onProgress?: (percent: number) => void }} options
 *   新增视频需先创建记录拿到 id 再上传；编辑时传当前视频 id
 * @returns {Promise<{ video_id: number, video_url?: string, transcode_status?: number }>}
 */
export async function uploadVideoFile(file, { videoId, onProgress } = {}) {
  const task = await initAdminVideoUpload({
    file_name: file.name,
    file_size: file.size,
    video_id: videoId,
  })

  const partSize = Number(task.part_size) || 1024 * 1024
  const totalParts = Number(task.parts) || Math.max(1, Math.ceil(file.size / partSize))

  for (let index = 0; index < totalParts; index += 1) {
    const start = index * partSize
    const chunk = file.slice(start, Math.min(start + partSize, file.size))
    await uploadAdminVideoChunk({
      oss_upload_id: task.oss_upload_id,
      part_number: index + 1,
      file: chunk,
    })
    onProgress?.(Math.round(((index + 1) / totalParts) * 100))
  }

  return completeAdminVideoUpload({
    oss_upload_id: task.oss_upload_id,
    video_id: videoId,
  })
}
