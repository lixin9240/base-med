import seed from '@/data/seed.json'

export const STORE_VERSION = seed.storeVersion
export const PASS_PERCENT = seed.passPercent ?? 60
export const WATCH_PERCENT = 90

const KEYS = {
  version: 'pv.store.version',
  courses: 'pv.courses',
  questions: 'pv.questions',
  records: 'pv.records',
  learners: 'pv.learners',
  progress: 'pv.progress',
  identity: 'pv.identity',
}

function read(key, fallback) {
  try {
    const raw = localStorage.getItem(key)
    if (raw === null) return fallback
    return JSON.parse(raw)
  } catch (e) {
    console.error('[store] read failed', key, e)
    return fallback
  }
}

function write(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value))
    return true
  } catch (e) {
    console.error('[store] write failed', key, e)
    return false
  }
}

export function ensureStoreSeed() {
  if (read(KEYS.version, '') === STORE_VERSION) return
  write(KEYS.version, STORE_VERSION)
  write(KEYS.courses, seed.courses)
  write(KEYS.questions, seed.questions)
  write(KEYS.records, [])
  write(KEYS.learners, [])
  write(KEYS.progress, {})
}

export function listPublishedCourses() {
  return read(KEYS.courses, [])
    .filter((c) => c.status === 'published')
    .sort((a, b) => (a.order ?? 999) - (b.order ?? 999) || (b.createdAt ?? 0) - (a.createdAt ?? 0))
}

export function getCourse(id) {
  return read(KEYS.courses, []).find((c) => c.id === id) ?? null
}

export function listQuestions(courseId, stage) {
  let list = read(KEYS.questions, [])
  if (courseId) list = list.filter((q) => q.courseId === courseId)
  if (stage) list = list.filter((q) => q.stage === stage)
  return list.sort((a, b) => (a.order ?? 999) - (b.order ?? 999))
}

export function findCourseByTitle(title) {
  const target = String(title ?? '').trim()
  if (!target) return null
  return read(KEYS.courses, []).find((c) => String(c.title ?? '').trim() === target) ?? null
}

/**
 * 整组替换某课程某阶段的题目（导入时使用）。
 */
export function replaceQuestions(courseId, stage, questions) {
  const all = read(KEYS.questions, [])
  const next = all
    .filter((q) => !(q.courseId === courseId && q.stage === stage))
    .concat(questions)
  write(KEYS.questions, next)
  return questions.length
}

export function getProgress(userId, courseId) {
  const all = read(KEYS.progress, {})
  return (
    all[`${userId}|${courseId}`] ?? {
      watchedPercent: 0,
      preDone: false,
      prePercent: 0,
      postDone: false,
      bestPercent: 0,
      passed: false,
      attempts: 0,
      lastRecordId: '',
      updatedAt: 0,
    }
  )
}

export function saveProgress(userId, courseId, patch) {
  const all = read(KEYS.progress, {})
  const key = `${userId}|${courseId}`
  all[key] = { ...getProgress(userId, courseId), ...patch, updatedAt: Date.now() }
  write(KEYS.progress, all)
  return all[key]
}

export function saveRecord(record) {
  const list = read(KEYS.records, [])
  list.push(record)
  write(KEYS.records, list)
  return record
}

export function getRecord(id) {
  return read(KEYS.records, []).find((r) => r.id === id) ?? null
}

function nextUserSeq(year) {
  let max = 0
  for (const learner of read(KEYS.learners, [])) {
    const m = /^DR-(\d{4})-(\d{6})$/.exec(String(learner.userId || ''))
    if (m && m[1] === String(year)) {
      const n = parseInt(m[2], 10)
      if (n > max) max = n
    }
  }
  return max + 1
}

function formatUserId(seq, year = new Date().getFullYear()) {
  return `DR-${year}-${String(seq).padStart(6, '0')}`
}

export function resolveOpenId() {
  const fromQuery = new URLSearchParams(window.location.search).get('openid')
  if (fromQuery) {
    localStorage.setItem(KEYS.identity, fromQuery)
    return fromQuery
  }
  const saved = localStorage.getItem(KEYS.identity)
  if (saved) return saved
  const generated = `wx_${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`
  localStorage.setItem(KEYS.identity, generated)
  return generated
}

export function ensureLearner() {
  ensureStoreSeed()
  const openid = resolveOpenId()
  const learners = read(KEYS.learners, [])
  let learner = learners.find((l) => l.openid === openid)
  if (learner) return learner
  const year = new Date().getFullYear()
  learner = {
    openid,
    userId: formatUserId(nextUserSeq(year), year),
    createdAt: Date.now(),
  }
  learners.push(learner)
  write(KEYS.learners, learners)
  return learner
}

export function newId(prefix = 'id_') {
  return prefix + Date.now().toString(36) + Math.random().toString(36).slice(2, 7)
}
