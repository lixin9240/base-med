/** 去掉文件名中的系统非法字符 */
export function safeFileName(value) {
  return String(value ?? '').replace(/[\\/:*?"<>|]/g, '_')
}

/** 触发浏览器下载 Blob */
export function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  // 立即 revoke 在部分浏览器会取消下载，延迟释放
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
