/**
 * 预设提示词发给统一后端合成并播放。
 * 地址与其它接口相同，来自 VITE_API_BASE_URL。
 */

import { toast } from '@/utils/toast'

const speechBase = String(import.meta.env.VITE_API_BASE_URL || 'http://47.108.175.101:6327').replace(/\/$/, '')

let player = null
let objectUrl = ''
let settled = Promise.resolve()
let finishCurrent = () => {}
const prepared = new Map()

function showStatus(text) {
  if (!import.meta.env.DEV || typeof document === 'undefined') return
  let node = document.getElementById('speech-debug')
  if (!node) {
    node = document.createElement('div')
    node.id = 'speech-debug'
    node.setAttribute('aria-live', 'polite')
    node.style.cssText = [
      'position:fixed',
      'left:50%',
      'top:8px',
      'transform:translateX(-50%)',
      'z-index:80',
      'max-width:92vw',
      'padding:4px 10px',
      'border-radius:999px',
      'background:rgb(0 0 0 / 0.72)',
      'color:#fff',
      'font-size:0.75em',
      'line-height:1.4',
      'pointer-events:none',
    ].join(';')
    document.body.appendChild(node)
  }
  node.textContent = text
}

function stopLocal() {
  if (player) {
    player.pause()
    player.src = ''
    player = null
  }
  if (objectUrl) {
    URL.revokeObjectURL(objectUrl)
    objectUrl = ''
  }
}

function normalize(text) {
  return String(text || '').replace(/\s+/g, ' ').trim()
}

async function fetchAudio(line) {
  const response = await fetch(`${speechBase}/api/public/speech`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text: line }),
  })
  const data = await response.json().catch(() => null)
  if (!response.ok || !data || data.code !== 0 || !data.data?.audio_base64) {
    throw new Error(data?.msg || '语音服务未连上')
  }
  const binary = atob(data.data.audio_base64)
  const bytes = new Uint8Array(binary.length)
  for (let index = 0; index < binary.length; index += 1) {
    bytes[index] = binary.charCodeAt(index)
  }
  return new Blob([bytes], { type: 'audio/mpeg' })
}

function playBlob(blob) {
  stopLocal()
  objectUrl = URL.createObjectURL(blob)
  const audio = new Audio(objectUrl)
  player = audio
  showStatus('语音 · 播放中')
  settled = new Promise((resolve) => {
    finishCurrent = resolve
    audio.onended = () => {
      showStatus('语音 · 已播放')
      resolve()
    }
    audio.onerror = () => resolve()
    audio.play().catch(() => {
      showStatus('语音 · 未能自动播放')
      toast('语音未能自动播放')
      resolve()
    })
  })
}

function failSpeech(error) {
  const message = error?.message || '语音服务未连上'
  showStatus(`语音 · ${message}`)
  toast(message)
  settled = Promise.resolve()
}

/** 提前合成，不播放。开屏动画期间调用，点开始时就能立刻出声。 */
export function prepareSpeech(text) {
  const line = normalize(text)
  if (!line || prepared.has(line)) return
  showStatus('语音 · 合成中')
  const entry = { blob: null, error: null, promise: null }
  entry.promise = fetchAudio(line)
    .then((blob) => {
      entry.blob = blob
      showStatus('语音 · 已就绪')
      return blob
    })
    .catch((error) => {
      entry.error = error
      showStatus(`语音 · ${error?.message || '合成失败'}`)
      throw error
    })
  prepared.set(line, entry)
}

/** 朗读一句中文。合成失败时跳过，不卡住后面的页面。 */
export function speak(text) {
  const line = normalize(text)
  if (!line) return
  finishCurrent()
  prepareSpeech(line)
  const entry = prepared.get(line)
  if (entry.blob) {
    playBlob(entry.blob)
    return
  }
  if (entry.error) {
    failSpeech(entry.error)
    return
  }
  settled = entry.promise
    .then((blob) => {
      playBlob(blob)
      return settled
    })
    .catch((error) => failSpeech(error))
}

/** 当前这句读完。没有在读时立即结束。 */
export function whenSpeechEnds() {
  return settled
}
