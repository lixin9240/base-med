import { get, post } from '../http'

const LIST_PATH = '/api/admin/video/list'
const DETAIL_PATH = '/api/admin/video/detail'
const CREATE_PATH = '/api/admin/video/create'
const UPDATE_PATH = '/api/admin/video/update'
const UPDATE_STATUS_PATH = '/api/admin/video/update-status'
const DELETE_PATH = '/api/admin/video/delete'
const UPLOAD_INIT_PATH = '/api/admin/video/upload/init'
const UPLOAD_CHUNK_PATH = '/api/admin/video/upload/chunk'
const UPLOAD_COMPLETE_PATH = '/api/admin/video/upload/complete'
const UPLOAD_COVER_PATH = '/api/admin/video/upload/cover'

/** 视频列表（管理端）。返回 { list, total, page, pageSize }，list 内字段保持后端命名。 */
export async function listAdminVideos({ page = 1, pageSize = 20, keyword = '' } = {}) {
  const params = new URLSearchParams({ page: String(page), page_size: String(pageSize) })
  if (keyword && keyword.trim()) {
    params.set('keyword', keyword.trim())
  }
  const body = await get(`${LIST_PATH}?${params.toString()}`)

  // 后端未就绪时可能拿到 HTML 等非信封响应，这种情况必须报错，不能当成「暂无数据」
  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('视频列表加载失败：服务未返回标准数据')
  }

  const data = body.data ?? {}
  return {
    list: Array.isArray(data.list) ? data.list : [],
    total: Number(data.total) || 0,
    page: Number(data.page) || page,
    pageSize: Number(data.page_size) || pageSize,
  }
}

/** 视频详情（编辑弹窗回填）。返回后端原始字段对象 */
export async function getAdminVideoDetail(id) {
  const body = await get(`${DETAIL_PATH}?id=${id}`)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('视频详情加载失败：服务未返回标准数据')
  }
  return body.data
}

/** 新增视频（2.2.3），payload 用后端字段名。返回新建记录，含 { id } */
export async function createAdminVideo(payload) {
  const body = await post(CREATE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('视频创建失败：服务未返回标准数据')
  }
  return body.data
}

/**
 * 上传封面图（multipart/form-data）。
 * 用户端封面与管理端列表缩略图共用这一张图，返回 OSS 地址，随新增 / 编辑写入 cover_url。
 */
export async function uploadAdminVideoCover(file) {
  const form = new FormData()
  form.append('file', file)

  const body = await post(UPLOAD_COVER_PATH, form)
  const url = body?.data?.url

  if (!url) {
    throw new Error('封面上传失败：服务未返回地址')
  }
  return url
}

/** 编辑视频（2.2.4），payload 用后端字段名 */
export async function updateAdminVideo(payload) {
  const body = await post(UPDATE_PATH, payload)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('视频保存失败：服务未返回标准数据')
  }
  return body.data ?? null
}

/** 删除视频（2.2.6 软删除）。已存在答题记录等引用时后端前置校验不通过，会抛错 */
export async function deleteAdminVideo({ id }) {
  const body = await post(DELETE_PATH, { id })

  // 文档未约定返回体；HTTP 失败 / code!==0 已在 http 层抛错，这里只拦显式失败
  if (!body || typeof body !== 'object' || body.success === false) {
    throw new Error('视频删除失败：服务未返回成功结果')
  }
  return true
}

/** 初始化视频分片上传（2.2.7，本地上传）。返回 { oss_upload_id, oss_path, part_size, parts }；存在未完成任务时返回原 oss_upload_id（断点续传） */
export async function initAdminVideoUpload({ file_name, file_size, video_id } = {}) {
  const body = await post(UPLOAD_INIT_PATH, { file_name, file_size, video_id })

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('上传初始化失败：服务未返回标准数据')
  }
  return body.data
}

/** 上传视频分片（本地模拟；OSS 直传时由前端 SDK 直传，可跳过） */
export async function uploadAdminVideoChunk({ oss_upload_id, part_number, file }) {
  const form = new FormData()
  form.append('oss_upload_id', oss_upload_id)
  form.append('part_number', String(part_number))
  // 分片 Blob 需要文件名，Laravel 才能识别为上传文件
  form.append('file', file, `part_${part_number}`)

  const body = await post(UPLOAD_CHUNK_PATH, form)

  if (!body || typeof body !== 'object' || !('data' in body)) {
    throw new Error('分片上传失败：服务未返回标准数据')
  }
  return body.data ?? {}
}

/** 完成视频上传（2.2.8）：合并分片并触发转码，返回 { video_id, video_url, transcode_status }；转码异步，状态经 2.2.1/2.2.2 查看 */
export async function completeAdminVideoUpload({ oss_upload_id, video_id }) {
  const body = await post(UPLOAD_COMPLETE_PATH, { oss_upload_id, video_id })

  if (!body || typeof body !== 'object' || !('data' in body) || !body.data) {
    throw new Error('完成上传失败：服务未返回标准数据')
  }
  return body.data
}

/** 上架 / 下架切换，status：0 已下架，1 已上架 */
export async function updateAdminVideoStatus({ id, status }) {
  const body = await post(UPDATE_STATUS_PATH, { id, status })

  if (!body || typeof body !== 'object' || body.success !== true) {
    throw new Error('状态切换失败：服务未返回成功结果')
  }
  return true
}
