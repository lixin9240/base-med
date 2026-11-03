/**
 * 浏览器自带朗读，用来先听五个节点的效果。
 * 句子由调用方按当场的标题、正确率拼出来，不播放固定音频。
 */

let active = null
let settled = Promise.resolve()
let timer = 0

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

/** 朗读一句中文。没有语音引擎时直接跳过。 */
export function speak(text) {
  const synth = engine()
  const line = String(text || '').replace(/\s+/g, ' ').trim()
  if (!synth || !line) return

  window.clearTimeout(timer)
  synth.resume()
  synth.cancel()

  const utter = new SpeechSynthesisUtterance(line)
  utter.lang = 'zh-CN'
  utter.rate = 1
  const voice = pickVoice(synth)
  if (voice) utter.voice = voice
  active = utter
  settled = new Promise((resolve) => {
    utter.onend = () => resolve()
    utter.onerror = () => resolve()
  })

  // Chrome 在 cancel 之后立刻 speak 会把这一句丢掉，稍等再播。
  timer = window.setTimeout(() => {
    if (active !== utter) return
    synth.resume()
    synth.speak(utter)
  }, 50)
}

/** 当前这句读完。没有在读时立即结束。 */
export function whenSpeechEnds() {
  return settled
}
