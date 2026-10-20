// 管理端 mock 数据
import seed from '@/data/seed.json'

// 视频管理列表
export const adminVideos = [
  {
    id: 1,
    title: '心肺复苏基础操作',
    category: '急救技能',
    speaker: '郭军 · 主任医师',
    pre: '3/3',
    preTone: 'success',
    post: '3/3',
    postTone: 'success',
    published: true,
    attempts: '12 次',
    accuracy: '67%',
    accuracyTone: 'warning',
  },
  {
    id: 2,
    title: '高血压患者的日常管理',
    category: '慢病管理',
    speaker: '王雅琴 · 副主任医师',
    pre: '3/3',
    preTone: 'success',
    post: '3/3',
    postTone: 'success',
    published: true,
    attempts: '12 次',
    accuracy: '75%',
    accuracyTone: 'warning',
  },
  {
    id: 3,
    title: '胸片检查前的准备与配合',
    category: '影像检查',
    speaker: '陈志远 · 主治医师',
    pre: '3/3',
    preTone: 'success',
    post: '3/3',
    postTone: 'success',
    published: true,
    attempts: '13 次',
    accuracy: '92%',
    accuracyTone: 'success',
  },
  {
    id: 4,
    title: '儿童疫苗接种后不良反应识别',
    category: '妇幼保健',
    speaker: '未指定',
    pre: '1/3',
    preTone: 'warning',
    post: '0/3',
    postTone: 'danger',
    published: false,
    attempts: '0 次',
    accuracy: '',
    accuracyTone: '',
  },
]

// ===== 答题记录 mock =====
// 基于题库真实题目生成，「查看作答」可看到完整逐题详情
const COURSES = Object.fromEntries(seed.courses.map((c) => [c.id, c]))
const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F']

function questionsOf(courseId, stage) {
  return seed.questions
    .filter((q) => q.courseId === courseId && q.stage === stage)
    .sort((a, b) => (a.order ?? 999) - (b.order ?? 999))
}

function wrongAnswer(question, index) {
  const { type, options, answer } = question
  if (type === 'judge') return answer === 'A' ? 'B' : 'A'
  if (type === 'multiple') {
    const pool = LETTERS.slice(0, options.length)
    const wrong = pool.find((l) => !answer.includes(l))
    return wrong ? [wrong] : answer.slice(0, -1)
  }
  const ansIdx = LETTERS.indexOf(String(answer).toUpperCase())
  const pick = (ansIdx + 1 + index) % options.length
  return LETTERS[pick === ansIdx ? (pick + 1) % options.length : pick]
}

function buildStageDetail(courseId, stage, correctCount) {
  return questionsOf(courseId, stage).map((q, i) => {
    const correct = i < correctCount
    return {
      questionId: q.id,
      stem: q.stem,
      type: q.type,
      options: q.options,
      userAnswer: correct ? q.answer : wrongAnswer(q, i),
      answer: q.answer,
      correct,
      analysis: q.analysis || '',
    }
  })
}

// ===== 用户管理 mock =====
const SURNAMES = [
  ['张', 'zhang'], ['王', 'wang'], ['李', 'li'], ['赵', 'zhao'], ['刘', 'liu'],
  ['陈', 'chen'], ['杨', 'yang'], ['黄', 'huang'], ['周', 'zhou'], ['吴', 'wu'],
  ['徐', 'xu'], ['孙', 'sun'], ['马', 'ma'], ['朱', 'zhu'], ['胡', 'hu'],
  ['郭', 'guo'], ['何', 'he'], ['高', 'gao'], ['林', 'lin'], ['罗', 'luo'],
  ['郑', 'zheng'], ['梁', 'liang'], ['谢', 'xie'], ['唐', 'tang'], ['邓', 'deng'],
  ['冯', 'feng'], ['韩', 'han'], ['曹', 'cao'], ['曾', 'zeng'], ['彭', 'peng'],
]

const GIVEN = [
  ['伟', 'wei'], ['芳', 'fang'], ['娜', 'na'], ['敏', 'min'], ['静', 'jing'],
  ['强', 'qiang'], ['磊', 'lei'], ['洋', 'yang'], ['艳', 'yan'], ['勇', 'yong'],
  ['军', 'jun'], ['杰', 'jie'], ['娟', 'juan'], ['涛', 'tao'], ['明', 'ming'],
  ['超', 'chao'], ['秀兰', 'xiulan'], ['霞', 'xia'], ['平', 'ping'], ['刚', 'gang'],
  ['桂英', 'guiying'], ['建国', 'jianguo'], ['文', 'wen'], ['丽', 'li'], ['斌', 'bin'],
  ['雪', 'xue'], ['辉', 'hui'], ['鹏', 'peng'], ['宇', 'yu'], ['梅', 'mei'],
]

const DOMAINS = ['163.com', 'qq.com', '126.com', 'outlook.com', 'foxmail.com', 'sina.com']
const PHONE_PREFIX = ['38', '39', '50', '58', '86', '88']

/** 由种子生成固定数字串，保证每次刷新数据一致 */
function pseudoDigits(seed, len) {
  let s = seed * 9301 + 49297
  let out = ''
  for (let i = 0; i < len; i += 1) {
    s = (s * 9301 + 49297) % 233280
    out += String(Math.floor((s / 233280) * 10))
  }
  return out
}

export const adminUsers = Array.from({ length: 63 }, (_, i) => {
  const [surname, surnamePy] = SURNAMES[i % SURNAMES.length]
  const [given, givenPy] = GIVEN[Math.floor(i / SURNAMES.length) % GIVEN.length]
  return {
    id: `u_${String(i + 1).padStart(3, '0')}`,
    name: surname + given,
    email: `${surnamePy}${givenPy}@${DOMAINS[i % DOMAINS.length]}`,
    phone: `1${PHONE_PREFIX[i % PHONE_PREFIX.length]}${pseudoDigits(i + 7, 8)}`,
    disabled: i % 21 === 0,
  }
})

// [时间, 课程, 学员序号, 前测答对, 看课答题答对]
const MOCK_ROWS = [
  ['2026-10-18 10:40', 'c_cpr', 3, 2, 3],
  ['2026-10-17 10:40', 'c_xray', 6, 3, 3],
  ['2026-10-17 10:40', 'c_xray', 10, 1, 3],
  ['2026-10-17 10:40', 'c_cpr', 15, 2, 3],
  ['2026-10-17 10:40', 'c_cpr', 16, 3, 3],
  ['2026-10-16 10:40', 'c_cpr', 1, 1, 0],
  ['2026-10-16 10:40', 'c_xray', 1, 0, 3],
  ['2026-10-16 10:40', 'c_cpr', 6, 2, 1],
  ['2026-10-15 10:40', 'c_bp', 5, 2, 1],
  ['2026-10-14 10:40', 'c_cpr', 14, 1, 2],
  ['2026-10-13 10:40', 'c_xray', 3, 2, 3],
  ['2026-10-13 10:40', 'c_xray', 7, 0, 3],
  ['2026-10-12 10:40', 'c_bp', 13, 1, 2],
  ['2026-10-11 10:40', 'c_xray', 9, 1, 0],
  ['2026-10-11 10:40', 'c_cpr', 11, 0, 2],
  ['2026-10-11 10:40', 'c_xray', 13, 2, 3],
  ['2026-10-10 10:40', 'c_bp', 2, 2, 3],
  ['2026-10-09 10:40', 'c_bp', 6, 3, 3],
  ['2026-10-09 10:40', 'c_cpr', 7, 1, 3],
  ['2026-10-09 10:40', 'c_bp', 8, 3, 2],
  ['2026-10-08 15:22', 'c_xray', 4, 2, 2],
  ['2026-10-08 09:15', 'c_bp', 12, 3, 3],
  ['2026-10-07 16:48', 'c_cpr', 2, 3, 2],
  ['2026-10-07 11:05', 'c_xray', 16, 1, 1],
  ['2026-10-06 14:37', 'c_bp', 9, 0, 2],
  ['2026-10-06 10:12', 'c_cpr', 18, 2, 3],
  ['2026-10-05 17:26', 'c_xray', 5, 3, 3],
  ['2026-10-05 08:59', 'c_bp', 17, 1, 0],
  ['2026-10-04 15:44', 'c_cpr', 10, 0, 1],
  ['2026-10-04 10:31', 'c_xray', 12, 2, 3],
  ['2026-10-03 16:07', 'c_bp', 4, 2, 3],
  ['2026-10-03 09:53', 'c_cpr', 9, 3, 2],
  ['2026-10-02 14:18', 'c_xray', 8, 1, 2],
  ['2026-10-02 11:36', 'c_bp', 15, 0, 3],
  ['2026-10-01 16:55', 'c_cpr', 5, 1, 2],
  ['2026-10-01 10:09', 'c_xray', 11, 2, 1],
  ['2026-10-01 08:47', 'c_bp', 1, 3, 3],
]

export const adminRecords = MOCK_ROWS.map(([time, courseId, seq, preCorrect, postCorrect], idx) => {
  const course = COURSES[courseId]
  const preTotal = questionsOf(courseId, 'pre').length
  const postTotal = questionsOf(courseId, 'post').length
  const percent = postTotal ? Math.round((postCorrect / postTotal) * 100) : 0
  const completedAt = new Date(time.replace(' ', 'T')).getTime()
  return {
    id: `r_mock_${idx + 1}`,
    userId: `DR-2026-${String(seq).padStart(6, '0')}`,
    courseId,
    courseTitle: course?.title ?? '',
    preCorrect,
    preTotal,
    prePercent: preTotal ? Math.round((preCorrect / preTotal) * 100) : 0,
    postCorrect,
    postTotal,
    percent,
    passed: percent >= (seed.passPercent ?? 60),
    detail: [
      ...buildStageDetail(courseId, 'pre', preCorrect),
      ...buildStageDetail(courseId, 'post', postCorrect),
    ],
    startedAt: completedAt - 5 * 60 * 1000,
    completedAt,
    mock: true,
  }
})
