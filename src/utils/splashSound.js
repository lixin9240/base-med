/**
 * 开屏短音。按动画时间点合成，不使用外部音频文件。
 * 浏览器拦住自动播放时放弃这一轮，避免动画结束后才补放。
 */

let shared = null

function audioContext() {
  if (shared) return shared
  const Ctx = window.AudioContext || window.webkitAudioContext
  if (!Ctx) return null
  shared = new Ctx()
  return shared
}

function reducedMotion() {
  return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
}

function busFor(audio) {
  const gain = audio.createGain()
  gain.gain.value = 1
  gain.connect(audio.destination)
  return gain
}

function blip(audio, bus, at, freq, dur, peak, type = 'sine') {
  const osc = audio.createOscillator()
  const gain = audio.createGain()
  osc.type = type
  osc.frequency.setValueAtTime(freq, at)
  gain.gain.setValueAtTime(0.0001, at)
  gain.gain.exponentialRampToValueAtTime(peak, at + Math.min(0.018, dur * 0.35))
  gain.gain.exponentialRampToValueAtTime(0.0001, at + dur)
  osc.connect(gain)
  gain.connect(bus)
  osc.start(at)
  osc.stop(at + dur + 0.02)
}

function sweep(audio, bus, at, from, to, dur, peak) {
  const osc = audio.createOscillator()
  const gain = audio.createGain()
  osc.type = 'sine'
  osc.frequency.setValueAtTime(from, at)
  osc.frequency.exponentialRampToValueAtTime(Math.max(to, 1), at + dur)
  gain.gain.setValueAtTime(0.0001, at)
  gain.gain.exponentialRampToValueAtTime(peak, at + 0.05)
  gain.gain.exponentialRampToValueAtTime(0.0001, at + dur)
  osc.connect(gain)
  gain.connect(bus)
  osc.start(at)
  osc.stop(at + dur + 0.02)
}

function schedule(audio, bus, t) {
  ;[0.23, 0.35, 0.53, 0.63].forEach((when, index) => {
    blip(audio, bus, t + when, 460 + index * 80, 0.06, 0.22, 'triangle')
  })

  sweep(audio, bus, t + 1.2, 340, 680, 0.48, 0.2)
  blip(audio, bus, t + 1.58, 196, 0.08, 0.32)

  ;[
    [1.78, 523],
    [1.85, 659],
    [1.93, 784],
    [2, 1046],
  ].forEach(([when, freq]) => {
    blip(audio, bus, t + when, freq, 0.14, 0.28)
  })

  blip(audio, bus, t + 2.23, 880, 0.09, 0.16)
  blip(audio, bus, t + 3.16, 784, 0.11, 0.26)
  blip(audio, bus, t + 3.28, 1175, 0.16, 0.32)
}

/** 在点击进入答题的同一刻调用，让后面的开屏音可以被播放。 */
export function unlockSplashSound() {
  const audio = audioContext()
  if (!audio) return
  audio.resume().catch(() => {})
}

/** 对齐开屏动画播放一轮。返回停止函数，离开开屏时调用。 */
export function startSplashScore() {
  if (reducedMotion()) return () => {}
  const audio = audioContext()
  if (!audio) return () => {}

  const bus = busFor(audio)
  let played = false
  const born = performance.now()

  const arm = () => {
    if (played || audio.state !== 'running') return
    if (performance.now() - born > 1600) return
    played = true
    schedule(audio, bus, audio.currentTime + 0.04)
  }

  arm()
  audio.resume().then(arm).catch(() => {})

  return () => {
    played = true
    const now = audio.currentTime
    bus.gain.cancelScheduledValues(now)
    bus.gain.setValueAtTime(Math.max(bus.gain.value, 0.0001), now)
    bus.gain.exponentialRampToValueAtTime(0.0001, now + 0.04)
  }
}

/** 点击「开始答题」时的短确认音，赶在语音前面。 */
export function playSplashTap() {
  const audio = audioContext()
  if (!audio) return
  audio.resume().catch(() => {})
  const bus = busFor(audio)
  const t = audio.currentTime + 0.01
  blip(audio, bus, t, 660, 0.05, 0.34)
  blip(audio, bus, t + 0.045, 990, 0.07, 0.26)
}

/** 切题过场「叮」：复用开屏动画最末尾那一声（784 → 1175）。 */
export function playAnswerDing() {
  if (reducedMotion()) return
  const audio = audioContext()
  if (!audio) return
  audio.resume().catch(() => {})
  const bus = busFor(audio)
  const t = audio.currentTime + 0.01
  blip(audio, bus, t, 784, 0.11, 0.26)
  blip(audio, bus, t + 0.12, 1175, 0.16, 0.32)
}
