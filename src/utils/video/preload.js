const warmed = new Set()

/**
 * 尽早开始拉取视频，让进入播放页时浏览器已有缓存。
 * 同一个地址只发起一次。
 */
export function warmVideo(url) {
  const src = String(url || '').trim()
  if (!src || warmed.has(src) || typeof document === 'undefined') return
  warmed.add(src)

  const video = document.createElement('video')
  video.preload = 'auto'
  video.muted = true
  video.playsInline = true
  video.setAttribute('playsinline', '')
  video.setAttribute('aria-hidden', 'true')
  video.tabIndex = -1
  video.style.cssText = 'position:fixed;width:0;height:0;opacity:0;pointer-events:none'
  video.src = src
  document.body.appendChild(video)
  video.load()
}
