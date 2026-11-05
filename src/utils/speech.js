/**
 * 开发环境把预设提示词发给本机后端合成并播放。
 * 没有配置本机地址时，仍用浏览器自带朗读。
 */

import { toast } from '@/utils/toast'

const localBase = String(import.meta.env.VITE_SPEECH_API_BASE_URL || '').replace(/\/$/, '')

let player = null
let objectUrl = ''
let settled = Promise.resolve()
let finishCurrent = () => {}
const prepared = new Map()

function engine() {
  if (typeof window === 'undefined') return null
  return window.speechSynthesis || null
}

function pickVoice(synth) {
  const voices = synth.getVoices()
  return (
    voices.find((item) => item.lang === 'zh-CN') ||
    voices.find((item) => /^zh/i.test(item.lang || '')) ||
    null
  )
}

function showStatus(text) {
  if (!localBase || typeof document === 'undefined') return
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

function speakWithBrowser(line) {
  const synth = engine()
  if (!synth) return
  synth.cancel()
  const utter = new SpeechSynthesisUtterance(line)
  utter.lang = 'zh-CN'
  utter.rate = 1
  const voice = pickVoice(synth)
  if (voice) utter.voice = voice
  settled = new Promise((resolve) => {
    finishCurrent = resolve
    utter.onend = () => resolve()
    utter.onerror = () => resolve()
  })
  window.setTimeout(() => {
    synth.resume()
    synth.speak(utter)
  }, 50)
}

function normalize(text) {
  return String(text || '').replace(/\s+/g, ' ').trim()
}

async function fetchAudio(line) {
  const response = await fetch(`${localBase}/api/public/speech`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text: line }),
  })
  const data = await response.json().catch(() => null)
  if (!response.ok || !data || data.code !== 0 || !data.data?.audio_base64) {
    throw new Error(data?.msg || '本机语音服务未连上')
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
  showStatus('本机语音 · 播放中')
  settled = new Promise((resolve) => {
    finishCurrent = resolve
    audio.onended = () => {
      showStatus('本机语音 · 已播放')
      resolve()
    }
    audio.onerror = () => resolve()
    audio.play().catch(() => {
      showStatus('本机语音 · 未能自动播放')
      toast('语音未能自动播放')
      resolve()
    })
  })
}

function failSpeech(error) {
  const message = error?.message || '本机语音服务未连上'
  showStatus(`本机语音 · ${message}`)
  toast(message)
  settled = Promise.resolve()
}

/** 提前合成，不播放。开屏动画期间调用，点开始时就能立刻出声。 */
export function prepareSpeech(text) {
  const line = normalize(text)
  if (!line || !localBase || prepared.has(line)) return
  showStatus('本机语音 · 合成中')
  const entry = { blob: null, error: null, promise: null }
  entry.promise = fetchAudio(line)
    .then((blob) => {
      entry.blob = blob
      showStatus('本机语音 · 已就绪')
      return blob
    })
    .catch((error) => {
      entry.error = error
      showStatus(`本机语音 · ${error?.message || '合成失败'}`)
      throw error
    })
  prepared.set(line, entry)
}

/** 朗读一句中文。本机合成失败时跳过，不卡住后面的页面。 */
export function speak(text) {
  const line = normalize(text)
  if (!line) return
  finishCurrent()
  if (!localBase) {
    stopLocal()
    speakWithBrowser(line)
    return
  }
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
