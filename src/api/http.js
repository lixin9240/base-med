import { getAdminToken } from '@/store/admin/auth'

const baseURL = import.meta.env.VITE_API_BASE_URL ?? ''

async function parseBody(response) {
  const text = await response.text()
  if (!text) return null
  try {
    return JSON.parse(text)
  } catch {
    return text
  }
}

function resolveUrl(path) {
  return path.startsWith('http') ? path : `${baseURL.replace(/\/$/, '')}/${path.replace(/^\//, '')}`
}

function buildHeaders(options) {
  const headers = new Headers(options.headers)
  if (options.body && !(options.body instanceof FormData) && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  const token = getAdminToken()
  if (token && !headers.has('Authorization')) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  return headers
}

export async function request(path, options = {}) {
  const headers = buildHeaders(options)

  const response = await fetch(resolveUrl(path), {
    ...options,
    headers,
    body:
      options.body && typeof options.body === 'object' && !(options.body instanceof FormData)
        ? JSON.stringify(options.body)
        : options.body,
  })

  const data = await parseBody(response)

  if (!response.ok) {
    const error = new Error(data?.msg || data?.message || response.statusText || '请求失败')
    error.status = response.status
    error.code = data?.code
    error.data = data
    throw error
  }

  // 统一信封：code !== 0 视为业务失败
  if (data && typeof data === 'object' && 'code' in data && data.code !== 0) {
    const error = new Error(data.msg || '请求失败')
    error.code = data.code
    error.data = data
    throw error
  }

  return data
}

/**
 * 下载文件流（Excel 等）。返回 Blob。
 * 服务端出错时会返回 JSON 信封，这里按同一规则解析并抛错。
 */
export async function requestBlob(path, options = {}) {
  const response = await fetch(resolveUrl(path), {
    ...options,
    headers: buildHeaders(options),
  })

  const contentType = response.headers.get('Content-Type') ?? ''
  if (
    !response.ok ||
    contentType.includes('application/json') ||
    contentType.includes('text/html')
  ) {
    const data = await parseBody(response)

    if (!response.ok) {
      const error = new Error(data?.msg || data?.message || response.statusText || '下载失败')
      error.status = response.status
      error.code = data?.code
      error.data = data
      throw error
    }

    if (data && typeof data === 'object' && 'code' in data && data.code !== 0) {
      throw new Error(data.msg || '下载失败')
    }
    throw new Error('下载失败：服务未返回文件')
  }

  return response.blob()
}

export function get(path, options) {
  return request(path, { ...options, method: 'GET' })
}

export function post(path, body, options) {
  return request(path, { ...options, method: 'POST', body })
}

export function put(path, body, options) {
  return request(path, { ...options, method: 'PUT', body })
}

export function del(path, options) {
  return request(path, { ...options, method: 'DELETE' })
}
