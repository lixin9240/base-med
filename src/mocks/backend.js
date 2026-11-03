/**
 * 本地假数据。开发环境默认启用，请求不再打到后端。
 * 关闭方式见 docs/代码修改记录/2026-11-03.md
 *
 * 答题进度、观看进度、管理端改动都记在 sessionStorage，刷新后同一标签页还能接着走。
 */

const STORAGE_KEY = 'pv.mock.backend'
const WATCH_REQUIRED = 1
const PASS_SCORE = 60

// 抽奖：6 扇区医疗主题奖品，权重后台可配（后续接真实接口时替换）
const LOTTERY_DAILY_LIMIT = 3
const LOTTERY_PRIZES = [
  { id: 1, name: '远程会诊一次', type: 'real', weight: 4 },
  { id: 2, name: '专家挂号券', type: 'real', weight: 8 },
  { id: 3, name: '口腔护理套装', type: 'real', weight: 6 },
  { id: 4, name: '健康咨询 30 分钟', type: 'real', weight: 10 },
  { id: 5, name: '再来一次', type: 'retry', weight: 12 },
  { id: 6, name: '谢谢参与', type: 'none', weight: 27 },
]
const DEFAULT_LOTTERY_WINNERS = [
  { phone: '138****2468', prize_name: '专家挂号券' },
  { phone: '139****8801', prize_name: '口腔护理套装' },
  { phone: '137****6620', prize_name: '远程会诊一次' },
]

const QUESTIONS = {
  1: [
    {
      question_id: 101,
      type: 'single',
      stem: '胸口闷痛持续不缓解，首先应该怎么做？',
      options: [
        { key: 'A', content: '立即拨打急救电话' },
        { key: 'B', content: '自行加大降压药剂量' },
        { key: 'C', content: '等到第二天再去医院' },
      ],
      correct_answer: 'A',
      analysis: '持续胸痛可能是急性心脑血管事件，应马上呼叫急救，不要自行加药硬扛。',
    },
    {
      question_id: 102,
      type: 'single',
      stem: '测量血压，比较合适的做法是？',
      options: [
        { key: 'A', content: '刚爬完楼立刻测量' },
        { key: 'B', content: '静坐五分钟后再测量' },
        { key: 'C', content: '吃饭的同时测量' },
      ],
      correct_answer: 'B',
      analysis: '活动、进食都会让读数偏高。安静坐一会儿再量，才接近日常血压。',
    },
    {
      question_id: 103,
      type: 'multiple',
      stem: '下面哪些做法有助于控制血压？',
      options: [
        { key: 'A', content: '饮食少放盐' },
        { key: 'B', content: '保持规律运动' },
        { key: 'C', content: '长期熬夜' },
      ],
      correct_answer: 'A,B',
      analysis: '低盐和规律运动都有帮助。长期熬夜会让血压更难控制。',
    },
    {
      question_id: 104,
      type: 'judge',
      stem: '血压降到正常后，就可以自己把药停掉。',
      options: [
        { key: 'A', content: '正确' },
        { key: 'B', content: '错误' },
      ],
      correct_answer: '错误',
      analysis: '停药要由医生决定。自行停药后血压常会反弹。',
    },
    {
      question_id: 105,
      type: 'judge',
      stem: '胸痛同时出冷汗，应该尽快就医。',
      options: [
        { key: 'A', content: '正确' },
        { key: 'B', content: '错误' },
      ],
      correct_answer: '正确',
      analysis: '胸痛伴出汗是危险信号，需要尽快就医。',
    },
  ],
  2: [
    {
      question_id: 201,
      type: 'single',
      stem: '几种药一起吃时，更稳妥的做法是？',
      options: [
        { key: 'A', content: '按说明书或医嘱的时间分开吃' },
        { key: 'B', content: '全部研碎后一次冲服' },
        { key: 'C', content: '感觉不舒服就自行加量' },
      ],
      correct_answer: 'A',
      analysis: '不同药物的服用时间不一样，不要自行碾碎或加量。',
    },
    {
      question_id: 202,
      type: 'judge',
      stem: '别人吃着有效的药，自己也可以直接拿来吃。',
      options: [
        { key: 'A', content: '正确' },
        { key: 'B', content: '错误' },
      ],
      correct_answer: '错误',
      analysis: '用药要看自己的病情和身体情况，不能照搬别人的药。',
    },
  ],
}

function seedState() {
  return {
    nextId: 300,
    nextRecordId: 2,
    categories: [
      { id: 1, name: '慢病管理' },
      { id: 2, name: '用药安全' },
      { id: 3, name: '急救技能' },
    ],
    videos: [
      {
        id: 1,
        title: '胸口闷、头晕，先分清这两件事',
        category_id: 1,
        category_name: '慢病管理',
        cover_url: '',
        speaker: '林晓',
        speaker_title: '心内科主治医师',
        speaker_org: '市第一人民医院',
        brief_intro: '五个小问题，看片前看片后各答一次。',
        text_key_points: '胸痛持续不缓解要马上呼救。量血压前先安静坐五分钟。降到正常也不能自己停药。',
        video_url: '/videos/demo.mp4',
        status: 1,
        question_count: QUESTIONS[1].length,
        answer_times: 1,
        accuracy_rate: 80,
      },
      {
        id: 2,
        title: '药不能跟着别人吃',
        category_id: 2,
        category_name: '用药安全',
        cover_url: '',
        speaker: '周宁',
        speaker_title: '临床药师',
        speaker_org: '社区卫生服务中心',
        brief_intro: '这条没有视频，看完文字要点就可以答题。',
        text_key_points: '按时按量服药。不要把几种药研碎后一起冲。别人的药不要直接拿来用。',
        video_url: '',
        status: 1,
        question_count: QUESTIONS[2].length,
        answer_times: 0,
        accuracy_rate: null,
      },
    ],
    users: [
      {
        id: 1,
        username: 'admin',
        name: '系统管理员',
        email: 'admin@example.com',
        role: 'admin',
        status: 1,
        last_login_at: '2026-11-03 09:00:00',
        created_at: '2026-10-01 10:00:00',
      },
      {
        id: 2,
        username: 'editor',
        name: '内容编辑',
        email: 'editor@example.com',
        role: 'admin',
        status: 0,
        last_login_at: '',
        created_at: '2026-10-18 14:20:00',
      },
    ],
    records: [
      {
        id: 1,
        video_id: 1,
        video_title: '胸口闷、头晕，先分清这两件事',
        dr_id: 'DR-DEMO-0001',
        submit_at: '2026-11-02 16:30:00',
        pre_correct: 2,
        pre_total: 5,
        post_correct: 4,
        post_total: 5,
        accuracy_rate: 80,
        result_status: 'pass',
        preAnswers: [
          { question_id: 101, user_answer: 'A' },
          { question_id: 102, user_answer: 'A' },
          { question_id: 103, user_answer: 'A' },
          { question_id: 104, user_answer: '正确' },
          { question_id: 105, user_answer: '正确' },
        ],
        postAnswers: [
          { question_id: 101, user_answer: 'A' },
          { question_id: 102, user_answer: 'B' },
          { question_id: 103, user_answer: 'A,B' },
          { question_id: 104, user_answer: '错误' },
          { question_id: 105, user_answer: '错误' },
        ],
      },
    ],
    sessions: {},
    links: {},
    nextLotteryId: 1,
    lotteryRecords: [],
    lotteryWinners: DEFAULT_LOTTERY_WINNERS.map((item) => ({ ...item })),
  }
}

let state = loadState()

function loadState() {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (raw) return JSON.parse(raw)
  } catch {
    // 读不到就用种子数据
  }
  return seedState()
}

function saveState() {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state))
  } catch {
    // 忽略存储失败，当前页仍可用内存里的数据
  }
}

export function isMockEnabled() {
  return import.meta.env.DEV && import.meta.env.VITE_USE_MOCK !== 'false'
}

function ok(data, extra = {}) {
  return { code: 0, success: true, msg: 'ok', data, ...extra }
}

function fail(msg, code = 1) {
  return { code, success: false, msg, data: null }
}

function nowText() {
  const date = new Date()
  const pad = (value) => String(value).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
}

function todayKey() {
  return nowText().slice(0, 10)
}

function lotteryRecords() {
  if (!Array.isArray(state.lotteryRecords)) state.lotteryRecords = []
  return state.lotteryRecords
}

function lotteryWinners() {
  if (!Array.isArray(state.lotteryWinners)) state.lotteryWinners = DEFAULT_LOTTERY_WINNERS.map((item) => ({ ...item }))
  return state.lotteryWinners
}

function lotteryChances(drId) {
  const used = lotteryRecords().filter(
    (item) => item.dr_id === drId && item.date === todayKey() && item.consume,
  ).length
  return {
    total: LOTTERY_DAILY_LIMIT,
    used,
    remaining: Math.max(0, LOTTERY_DAILY_LIMIT - used),
  }
}

function maskPhone(phone) {
  return `${phone.slice(0, 3)}****${phone.slice(7)}`
}

function pickLotteryPrize() {
  const total = LOTTERY_PRIZES.reduce((sum, item) => sum + item.weight, 0)
  let roll = Math.random() * total
  for (const prize of LOTTERY_PRIZES) {
    if (roll < prize.weight) return prize
    roll -= prize.weight
  }
  return LOTTERY_PRIZES[LOTTERY_PRIZES.length - 1]
}

function lotteryPrizePayload(prize) {
  return {
    id: prize.id,
    name: prize.name,
    type: prize.type,
    sector_index: LOTTERY_PRIZES.findIndex((item) => item.id === prize.id),
  }
}

function lotteryInfoOf(drId) {
  const pending = lotteryRecords()
    .filter((item) => item.dr_id === drId && item.prize_type === 'real' && !item.claimed_at)
    .sort((a, b) => b.id - a.id)[0]

  return {
    prizes: LOTTERY_PRIZES.map(lotteryPrizePayload),
    chances: lotteryChances(drId),
    winners: lotteryWinners().slice(0, 8),
    pending_claim: pending
      ? {
          record_id: pending.id,
          prize_id: pending.prize_id,
          prize_name: pending.prize_name,
        }
      : null,
  }
}

function splitRequest(path) {
  const [pathname, search = ''] = String(path).split('?')
  return {
    pathname: pathname.replace(/\/$/, '') || '/',
    query: Object.fromEntries(new URLSearchParams(search)),
  }
}

function readBody(options) {
  const body = options?.body
  if (!body || body instanceof FormData) return {}
  if (typeof body === 'string') {
    try {
      return JSON.parse(body)
    } catch {
      return {}
    }
  }
  return body
}

function publicQuestion(question) {
  return {
    question_id: question.question_id,
    type: question.type,
    stem: question.stem,
    options: question.options,
  }
}

function findVideo(id) {
  return state.videos.find((item) => String(item.id) === String(id))
}

function questionsOf(videoId) {
  return QUESTIONS[Number(videoId)] || []
}

function sessionOf(drId, videoId) {
  if (!state.sessions[drId]) {
    state.sessions[drId] = {
      videoId: Number(videoId),
      preSubmitted: false,
      preAnswers: [],
      watched_seconds: 0,
      progress_percent: 0,
      is_unlocked: 0,
    }
  }
  return state.sessions[drId]
}

function answerMatched(question, userAnswer) {
  const expected = String(question.correct_answer)
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
    .sort()
  const actual = String(userAnswer ?? '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
    .sort()
  return expected.length === actual.length && expected.every((item, index) => item === actual[index])
}

function countCorrect(videoId, answers) {
  const questions = questionsOf(videoId)
  return questions.filter((question) => {
    const found = (answers || []).find((item) => String(item.question_id) === String(question.question_id))
    return found ? answerMatched(question, found.user_answer) : false
  }).length
}

function paginate(list, query) {
  const page = Number(query.page) || 1
  const pageSize = Number(query.page_size) || 20
  const start = (page - 1) * pageSize
  return {
    list: list.slice(start, start + pageSize),
    total: list.length,
    page,
    page_size: pageSize,
  }
}

function refreshVideoStats(video) {
  const related = state.records.filter((item) => String(item.video_id) === String(video.id))
  video.answer_times = related.length
  if (!related.length) {
    video.accuracy_rate = null
    return
  }
  const total = related.reduce((sum, item) => sum + Number(item.accuracy_rate || 0), 0)
  video.accuracy_rate = Math.round(total / related.length)
}

function buildResult(record) {
  const questions = questionsOf(record.video_id)
  return {
    exam_record_id: record.id,
    video_id: record.video_id,
    video_title: record.video_title,
    result_status: record.result_status,
    pass_accuracy: PASS_SCORE,
    accuracy_rate: record.accuracy_rate,
    pre: { correct: record.pre_correct, total: record.pre_total },
    post: { correct: record.post_correct, total: record.post_total },
    question_detail: questions.map((question) => {
      const post = (record.postAnswers || []).find((item) => String(item.question_id) === String(question.question_id))
      const correct = post ? answerMatched(question, post.user_answer) : false
      return {
        question_id: question.question_id,
        type: question.type,
        stem: question.stem,
        options: question.options,
        correct_answer: question.correct_answer,
        post_user_answer: post?.user_answer ?? '',
        post_is_correct: correct ? 1 : 0,
        analysis: question.analysis,
      }
    }),
  }
}

function handlePublic(pathname, query, body) {
  if (pathname === '/api/public/dr/create') {
    const drId = `DR-${Date.now().toString(36).toUpperCase()}`
    sessionOf(drId, query.video_id)
    saveState()
    return ok({ dr_id: drId })
  }

  if (pathname === '/api/public/video/list') {
    return ok(
      state.videos
        .filter((item) => Number(item.status) === 1)
        .map((item) => ({
          id: item.id,
          title: item.title,
          category_name: item.category_name,
          speaker: item.speaker,
          brief_intro: item.brief_intro,
          cover_url: item.cover_url,
        })),
    )
  }

  if (pathname === '/api/public/video/detail') {
    const video = findVideo(query.video_id)
    if (!video) return ok(null)
    const session = sessionOf(query.dr_id, video.id)
    return ok({
      ...video,
      exam_state: { pre_submitted: session.preSubmitted ? 1 : 0 },
      watch_progress: {
        progress_percent: session.progress_percent,
        watch_required_percent: WATCH_REQUIRED,
        is_unlocked: video.video_url ? session.is_unlocked : 1,
        watched_seconds: session.watched_seconds,
      },
    })
  }

  if (pathname === '/api/public/video/progress') {
    const session = sessionOf(body.dr_id, body.video_id)
    const duration = Number(body.duration) || 30
    const watched = Math.max(session.watched_seconds, Number(body.watched_seconds) || 0)
    const percent = Math.min(100, Math.round((watched / duration) * 100))
    session.watched_seconds = watched
    session.progress_percent = Math.max(session.progress_percent, percent)
    session.is_unlocked = session.progress_percent >= WATCH_REQUIRED ? 1 : 0
    saveState()
    return ok({
      progress_percent: session.progress_percent,
      watch_required_percent: WATCH_REQUIRED,
      is_unlocked: session.is_unlocked,
    })
  }

  if (pathname === '/api/public/video/question-list') {
    return ok(questionsOf(query.video_id).map(publicQuestion))
  }

  if (pathname === '/api/public/exam/submit-pre') {
    const session = sessionOf(body.dr_id, body.video_id)
    session.preSubmitted = true
    session.preAnswers = body.answers || []
    saveState()
    return ok({})
  }

  if (pathname === '/api/public/exam/submit-post' || pathname === '/api/public/exam/retake') {
    const video = findVideo(body.video_id)
    const session = sessionOf(body.dr_id, body.video_id)
    const postAnswers = body.answers || []
    const postTotal = questionsOf(body.video_id).length
    const postCorrect = countCorrect(body.video_id, postAnswers)
    const preTotal = postTotal
    const preCorrect = countCorrect(body.video_id, session.preAnswers)
    const accuracy = postTotal ? Math.round((postCorrect / postTotal) * 100) : 0
    const record = {
      id: state.nextRecordId++,
      video_id: Number(body.video_id),
      video_title: video?.title || '',
      dr_id: body.dr_id,
      submit_at: nowText(),
      pre_correct: preCorrect,
      pre_total: preTotal,
      post_correct: postCorrect,
      post_total: postTotal,
      accuracy_rate: accuracy,
      result_status: accuracy >= PASS_SCORE ? 'pass' : 'fail',
      preAnswers: session.preAnswers,
      postAnswers,
    }
    state.records.unshift(record)
    if (video) refreshVideoStats(video)
    saveState()
    return ok({ exam_record_id: record.id, video_id: record.video_id })
  }

  if (pathname === '/api/public/exam/result') {
    const record = state.records.find((item) => {
      if (query.exam_record_id) return String(item.id) === String(query.exam_record_id)
      return String(item.video_id) === String(query.video_id) && item.dr_id === query.dr_id
    })
    return ok(record ? buildResult(record) : null)
  }

  if (pathname === '/api/public/lottery/info') {
    if (!query.dr_id) return fail('缺少匿名编号')
    return ok(lotteryInfoOf(String(query.dr_id)))
  }

  if (pathname === '/api/public/lottery/draw') {
    const drId = String(body.dr_id || '')
    if (!drId) return fail('缺少匿名编号')
    if (lotteryChances(drId).remaining <= 0) return fail('今日抽奖次数已用完，明天再来')

    const prize = pickLotteryPrize()
    const id = state.nextLotteryId || 1
    state.nextLotteryId = id + 1
    lotteryRecords().unshift({
      id,
      dr_id: drId,
      video_id: Number(body.video_id) || null,
      exam_record_id: Number(body.exam_record_id) || null,
      prize_id: prize.id,
      prize_name: prize.name,
      prize_type: prize.type,
      sector_index: lotteryPrizePayload(prize).sector_index,
      consume: prize.type !== 'retry',
      phone: '',
      claimed_at: null,
      date: todayKey(),
      drawn_at: nowText(),
    })
    saveState()
    return ok({
      record_id: id,
      prize: lotteryPrizePayload(prize),
      chances: lotteryChances(drId),
    })
  }

  if (pathname === '/api/public/lottery/claim') {
    const drId = String(body.dr_id || '')
    const recordId = Number(body.record_id)
    const phone = String(body.phone || '').trim()
    if (!drId) return fail('缺少匿名编号')
    if (!/^1[3-9]\d{9}$/.test(phone)) return fail('请输入正确的 11 位手机号')

    const record = lotteryRecords().find(
      (item) => item.id === recordId && item.dr_id === drId,
    )
    if (!record) return fail('抽奖记录不存在')
    if (record.prize_type !== 'real') return fail('该奖品无需填写手机号')
    if (record.claimed_at) return fail('奖品已领取，请勿重复提交')

    record.phone = phone
    record.claimed_at = nowText()
    lotteryWinners().unshift({ phone: maskPhone(phone), prize_name: record.prize_name })
    saveState()
    return ok({
      record_id: record.id,
      phone: maskPhone(phone),
      prize_name: record.prize_name,
    })
  }

  return undefined
}

function handleAdmin(pathname, query, body) {
  if (pathname === '/api/admin/auth/login') {
    return ok({
      token: 'mock-admin-token',
      admin_info: { id: 1, username: body.username || 'local', name: '本地预览', status: 1 },
    })
  }
  if (pathname === '/api/admin/auth/profile') {
    return ok({ id: 1, username: 'local', name: '本地预览', status: 1 })
  }
  if (pathname === '/api/admin/auth/logout') return ok({})
  if (pathname === '/api/admin/auth/forgot-password') return ok({ expire_minutes: 10 })
  if (pathname === '/api/admin/auth/reset-password') return ok({})

  if (pathname === '/api/admin/category/list') return ok(state.categories)

  if (pathname === '/api/admin/video/list') {
    const keyword = String(query.keyword || '').trim().toLowerCase()
    const filtered = state.videos.filter((item) => {
      if (!keyword) return true
      return [item.title, item.speaker, item.category_name].some((text) =>
        String(text || '').toLowerCase().includes(keyword),
      )
    })
    return ok(paginate(filtered, query))
  }
  if (pathname === '/api/admin/video/detail') return ok(findVideo(query.id) || null)
  if (pathname === '/api/admin/video/create') {
    const id = state.nextId++
    const category = state.categories.find((item) => String(item.id) === String(body.category_id))
    state.videos.unshift({
      id,
      title: body.title || '未命名视频',
      category_id: body.category_id || null,
      category_name: category?.name || '',
      cover_url: body.cover_url || '',
      speaker: body.speaker || '',
      speaker_title: body.speaker_title || '',
      speaker_org: body.speaker_org || '',
      brief_intro: body.brief_intro || '',
      text_key_points: body.text_key_points || '',
      video_url: body.video_url || '',
      status: Number(body.status ?? 1),
      question_count: 0,
      answer_times: 0,
      accuracy_rate: null,
    })
    saveState()
    return ok({ id })
  }
  if (pathname === '/api/admin/video/update') {
    const video = findVideo(body.id)
    if (video) Object.assign(video, body)
    saveState()
    return ok(video || null)
  }
  if (pathname === '/api/admin/video/update-status') {
    const video = findVideo(body.id)
    if (video) video.status = Number(body.status)
    saveState()
    return ok({})
  }
  if (pathname === '/api/admin/video/delete') {
    state.videos = state.videos.filter((item) => String(item.id) !== String(body.id))
    saveState()
    return ok({})
  }
  if (pathname === '/api/admin/video/upload/cover') {
    return ok({ url: '/videos/demo.mp4' })
  }
  if (pathname === '/api/admin/video/upload/init') {
    return ok({ oss_upload_id: `mock-${Date.now()}`, oss_path: 'mock/demo.mp4', part_size: 5242880, parts: [] })
  }
  if (pathname === '/api/admin/video/upload/chunk') return ok({})
  if (pathname === '/api/admin/video/upload/complete') {
    const video = findVideo(body.video_id)
    if (video) video.video_url = '/videos/demo.mp4'
    saveState()
    return ok({ video_id: body.video_id, video_url: '/videos/demo.mp4', transcode_status: 1 })
  }

  if (pathname === '/api/admin/video-question/list') {
    const list = questionsOf(query.video_id).map((item, index) => ({ ...item, sort_order: index + 1 }))
    return ok({ list, total: list.length })
  }

  if (pathname === '/api/admin/exam-record/list') {
    const filtered = state.records.filter((item) => {
      if (query.video_id && String(item.video_id) !== String(query.video_id)) return false
      if (query.result_status && item.result_status !== query.result_status) return false
      return true
    })
    return ok(paginate(filtered, query))
  }
  if (pathname === '/api/admin/exam-record/detail') {
    const record = state.records.find((item) => String(item.id) === String(query.exam_record_id))
    if (!record) return ok(null)
    const questions = questionsOf(record.video_id)
    const list = ['pretest', 'posttest'].flatMap((stage) => {
      const answers = stage === 'pretest' ? record.preAnswers : record.postAnswers
      return questions.map((question) => {
        const found = (answers || []).find((item) => String(item.question_id) === String(question.question_id))
        return {
          question_id: question.question_id,
          type: question.type,
          stem: question.stem,
          stage,
          is_correct: found && answerMatched(question, found.user_answer) ? 1 : 0,
          correct_answer: question.correct_answer,
          user_answer: found?.user_answer ?? '',
        }
      })
    })
    return ok({
      video_title: record.video_title,
      dr_id: record.dr_id,
      submit_at: record.submit_at,
      result_status: record.result_status,
      accuracy_rate: record.accuracy_rate,
      pre: { correct: record.pre_correct, total: record.pre_total },
      post: { correct: record.post_correct, total: record.post_total },
      list,
    })
  }
  if (pathname === '/api/admin/exam-record/export') {
    return ok({ filename: '答题记录.csv', url: 'data:text/csv;charset=utf-8,dr_id,result%0ADR-DEMO-0001,pass' })
  }

  if (pathname === '/api/admin/admin-user/list') {
    const keyword = String(query.keyword || '').trim().toLowerCase()
    const filtered = state.users.filter((item) => {
      if (query.status !== undefined && query.status !== '' && String(item.status) !== String(query.status)) return false
      if (!keyword) return true
      return [item.username, item.name].some((text) => String(text || '').toLowerCase().includes(keyword))
    })
    const page = paginate(filtered, query)
    return ok({ list: page.list, total: page.total })
  }
  if (pathname === '/api/admin/admin-user/create') {
    const id = state.nextId++
    state.users.unshift({
      id,
      username: body.username || `user${id}`,
      name: body.name || '',
      email: body.email || '',
      role: body.role || 'admin',
      status: Number(body.status ?? 1),
      last_login_at: '',
      created_at: nowText(),
    })
    saveState()
    return ok({ id })
  }
  if (pathname === '/api/admin/admin-user/update') {
    const user = state.users.find((item) => String(item.id) === String(body.id))
    if (user) Object.assign(user, body)
    saveState()
    return ok({})
  }
  if (pathname === '/api/admin/admin-user/delete') {
    state.users = state.users.filter((item) => String(item.id) !== String(body.id))
    saveState()
    return ok({})
  }

  if (pathname === '/api/admin/statistics/overview') {
    const online = state.videos.filter((item) => Number(item.status) === 1).length
    const passed = state.records.filter((item) => item.result_status === 'pass').length
    const accuracy = state.records.length
      ? Math.round(state.records.reduce((sum, item) => sum + Number(item.accuracy_rate || 0), 0) / state.records.length)
      : 0
    return ok({
      video_total: state.videos.length,
      video_online: online,
      answer_total_times: state.records.length,
      avg_accuracy: accuracy,
      pass_rate: state.records.length ? Math.round((passed / state.records.length) * 100) : 0,
      pass_score: PASS_SCORE,
    })
  }
  if (pathname === '/api/admin/statistics/video-list') {
    return ok(
      state.videos.map((video) => {
        const related = state.records.filter((item) => String(item.video_id) === String(video.id))
        const passed = related.filter((item) => item.result_status === 'pass').length
        const preRate = related.length
          ? Math.round(
              (related.reduce((sum, item) => sum + (item.pre_total ? item.pre_correct / item.pre_total : 0), 0) /
                related.length) *
                100,
            )
          : null
        return {
          video_id: video.id,
          title: video.title,
          category_name: video.category_name,
          status: video.status,
          answer_times: related.length,
          pre_avg_accuracy: preRate,
          post_avg_accuracy: related.length
            ? Math.round(related.reduce((sum, item) => sum + Number(item.accuracy_rate || 0), 0) / related.length)
            : null,
          pass_rate: related.length ? Math.round((passed / related.length) * 100) : null,
        }
      }),
    )
  }
  if (pathname === '/api/admin/statistics/wrong-question') {
    const rows = []
    state.videos.forEach((video) => {
      questionsOf(video.id).forEach((question) => {
        const attempts = state.records.filter((item) => String(item.video_id) === String(video.id))
        if (!attempts.length) return
        const wrong = attempts.filter((item) => {
          const found = (item.postAnswers || []).find((answer) => String(answer.question_id) === String(question.question_id))
          return !found || !answerMatched(question, found.user_answer)
        }).length
        rows.push({
          question_id: question.question_id,
          stem: question.stem,
          video_title: video.title,
          stage: 'posttest',
          answer_times: attempts.length,
          wrong_rate: Math.round((wrong / attempts.length) * 100),
        })
      })
    })
    rows.sort((a, b) => b.wrong_rate - a.wrong_rate)
    return ok(rows)
  }

  if (pathname === '/api/admin/share-link/detail') {
    return ok(state.links[String(query.video_id)] || null)
  }
  if (pathname === '/api/admin/share-link/generate') {
    const link = {
      id: state.nextId++,
      video_id: Number(body.video_id),
      url: `${window.location.origin}/mobile/quiz?videoId=${body.video_id}&share=mock`,
      token: 'mock-share',
      expire_type: `${body.expire_days || 7}d`,
      expire_at: nowText(),
      visit_count: 0,
      status: 1,
      created_at: nowText(),
    }
    state.links[String(body.video_id)] = link
    saveState()
    return ok(link)
  }
  if (pathname === '/api/admin/share-link/revoke') {
    delete state.links[String(body.video_id)]
    saveState()
    return ok({})
  }

  return undefined
}

export function handleMockRequest(path, options = {}) {
  const { pathname, query } = splitRequest(path)
  const body = readBody(options)
  const method = String(options.method || 'GET').toUpperCase()
  if (!pathname.startsWith('/api/')) return undefined

  const hit = pathname.startsWith('/api/public/')
    ? handlePublic(pathname, query, body)
    : handleAdmin(pathname, query, body)

  if (hit !== undefined) return hit
  console.warn('[mock] 未单独编写的接口，返回空数据', method, pathname)
  return ok(null)
}

export function handleMockDownload() {
  const content = '名称,次数\n胸口闷、头晕，先分清这两件事,1\n'
  return {
    blob: new Blob([content], { type: 'text/csv;charset=utf-8' }),
    filename: '数据统计.csv',
  }
}
