import { clearAdminAuth, getAdminToken } from '@/store/admin/auth'

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
    // 401 未授权：清 token 跳登录
    if (response.status === 401) {
      clearAdminAuth()
      const current = window.location.pathname + window.location.search
      if (!current.startsWith('/admin/login')) {
        window.location.replace(`/admin/login?redirect=${encodeURIComponent(current)}`)
      }
    }
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

/** 从 Content-Disposition 解析文件名，优先 RFC 5987 的 filename*，兼容百分号编码 */
function parseFileName(disposition) {
  if (!disposition) return ''

  const encoded = /filename\*\s*=\s*(?:UTF-8'')?([^;]+)/i.exec(disposition)
  if (encoded) {
    try {
      return decodeURIComponent(encoded[1].trim().replace(/^"|"$/g, ''))
    } catch {
      // 编码异常时退回下面的 filename 解析
    }
  }

  const plain = /filename\s*=\s*(?:"([^"]+)"|([^;]+))/i.exec(disposition)
  const name = (plain?.[1] ?? plain?.[2] ?? '').trim()
  if (!name) return ''
  try {
    return decodeURIComponent(name)
  } catch {
    return name
  }
}

/**
 * 下载文件流（Excel 等）。返回 { blob, filename }，filename 取自 Content-Disposition（可能为空）。
 * 服务端出错时会返回 JSON 信封，这里按同一规则解析并抛错。
 */
export async function requestDownload(path, options = {}) {
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

  return {
    blob: await response.blob(),
    filename: parseFileName(response.headers.get('Content-Disposition')),
  }
}

/** 下载文件流（Excel 等）。只返回 Blob */
export async function requestBlob(path, options = {}) {
  const { blob } = await requestDownload(path, options)
  return blob
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
