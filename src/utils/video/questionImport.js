const BASE_URL = import.meta.env?.BASE_URL ?? '/'

export const TEMPLATE_PATH = `${BASE_URL}templates/科普视频题目_批量导入模板.xlsx`
export const TEMPLATE_SHEET = '题目模板'

const STAGE_MAP = {
  前测: 'pre',
  后测: 'post',
}

const TYPE_MAP = {
  单选题: 'single',
  多选题: 'multiple',
  判断题: 'judge',
}

const ANSWER_PATTERN = /^[A-F]+$/
const COLUMN_PATTERN = /[*＊]+$/

function text(value) {
  return value == null ? '' : String(value).trim()
}

function headerName(value) {
  return text(value).replace(COLUMN_PATTERN, '').trim()
}

const REQUIRED_COLUMNS = ['视频标题', '题目用途', '题序', '题型', '题干', '选项A', '选项B', '正确答案']

/**
 * 读取工作簿中「题目模板」表，返回原始二维数组。
 */
export async function readTemplateSheet(file) {
  // 按需加载解析库，避免一进视频管理页就下载 SheetJS
  const XLSX = await import('xlsx')
  const buffer = await file.arrayBuffer()
  const workbook = XLSX.read(buffer, { type: 'array' })
  const sheetName = workbook.SheetNames.includes(TEMPLATE_SHEET)
    ? TEMPLATE_SHEET
    : workbook.SheetNames[0]
  if (!sheetName) throw new Error('文件中没有工作表')

  const rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], {
    header: 1,
    defval: '',
    // 保留空行，保证行号与 Excel 中的实际行号一致
    blankrows: true,
  })
  if (!rows.length) throw new Error(`「${sheetName}」表是空的`)
  return rows
}

function mapColumns(headerRow) {
  const index = {}
  headerRow.forEach((raw, i) => {
    const name = headerName(raw)
    if (name && index[name] == null) index[name] = i
  })

  const missing = REQUIRED_COLUMNS.filter((name) => index[name] == null)
  if (missing.length) {
    throw new Error(`模板缺少列：${missing.join('、')}，请使用最新模板`)
  }

  return index
}

function parseAnswer(raw, type, optionLetters, errors) {
  const value = text(raw).replace(/[\s,，、;；/]+/g, '').toUpperCase()
  if (!value) {
    errors.push('正确答案不能为空')
    return null
  }
  if (!ANSWER_PATTERN.test(value)) {
    errors.push('正确答案应填 A~F 的大写字母（多选连写，如 ABC）')
    return null
  }

  const letters = [...new Set(value.split(''))].sort()

  if (type === 'multiple') {
    if (letters.length < 2) {
      errors.push('多选题的正确答案至少填 2 个字母')
      return null
    }
    const outside = letters.filter((letter) => !optionLetters.includes(letter))
    if (outside.length) {
      errors.push(`正确答案 ${outside.join('、')} 不在已填写的选项中`)
      return null
    }
    return letters
  }

  if (letters.length !== 1) {
    errors.push('单选题 / 判断题的正确答案只能填 1 个字母')
    return null
  }
  if (!optionLetters.includes(letters[0])) {
    errors.push(`正确答案 ${letters[0]} 不在已填写的选项中`)
    return null
  }
  return letters[0]
}

function collectOptions(row, columns, errors) {
  const values = []
  const letters = []
  let highest = -1

  for (const letter of ['A', 'B', 'C', 'D', 'E', 'F']) {
    const key = `选项${letter}`
    if (columns[key] == null) continue
    const value = text(row[columns[key]])
    if (value) {
      values.push(value)
      letters.push(letter)
      highest = letter.charCodeAt(0) - 65
    }
  }

  if (values.length < 2) {
    errors.push('至少需要填写选项A、选项B')
    return { options: [], letters: [] }
  }

  if (values.length !== highest + 1) {
    errors.push('选项需从 A 起连续填写，中间不能留空')
    return { options: [], letters: [] }
  }

  return { options: values, letters }
}

function validateRow(row, columns, rowNumber, videos, seenKeys, errors, items, expectedStage) {
  const videoTitle = text(row[columns['视频标题']])
  const stageLabel = text(row[columns['题目用途']])
  const orderRaw = text(row[columns['题序']])
  const typeLabel = text(row[columns['题型']])
  const stem = text(row[columns['题干']])
  const analysis = columns['答案解析'] == null ? '' : text(row[columns['答案解析']])

  if (!videoTitle) errors.push('视频标题不能为空')
  if (!stageLabel) errors.push('题目用途不能为空')
  if (!orderRaw) errors.push('题序不能为空')
  if (!typeLabel) errors.push('题型不能为空')
  if (!stem) errors.push('题干不能为空')

  const video = videos.find((item) => text(item.title) === videoTitle)
  if (videoTitle && !video) {
    errors.push(`未找到视频「${videoTitle}」，标题需与视频列表完全一致`)
  }

  const stage = STAGE_MAP[stageLabel]
  if (stageLabel && !stage) errors.push('题目用途只能填「前测」或「后测」')
  if (expectedStage && stage && stage !== expectedStage) {
    errors.push(`本组只能导入${expectedStage === 'pre' ? '前测' : '后测'}题目`)
  }

  const type = TYPE_MAP[typeLabel]
  if (typeLabel && !type) errors.push('题型只能填「单选题」「多选题」或「判断题」')

  const order = Number(orderRaw)
  if (orderRaw && (!Number.isInteger(order) || order < 1)) {
    errors.push('题序需填写大于 0 的整数')
  }

  let options = []
  let optionLetters = []
  if (type === 'judge') {
    const a = columns['选项A'] == null ? '' : text(row[columns['选项A']])
    const b = columns['选项B'] == null ? '' : text(row[columns['选项B']])
    if ((a && a !== '正确') || (b && b !== '错误')) {
      errors.push('判断题的选项A固定填「正确」，选项B固定填「错误」')
    }
    options = ['正确', '错误']
    optionLetters = ['A', 'B']
  } else if (type) {
    const result = collectOptions(row, columns, errors)
    options = result.options
    optionLetters = result.letters
  }

  const answer = type ? parseAnswer(row[columns['正确答案']], type, optionLetters, errors) : null

  if (errors.length) return

  const key = `${video.id}|${stage}|${order}`
  if (seenKeys.has(key)) {
    errors.push('同一视频、同一用途下的题序重复')
    return
  }
  seenKeys.add(key)

  items.push({
    rowNumber,
    videoId: video.id,
    videoTitle: video.title,
    courseId: video.courseId,
    stage,
    order,
    type,
    stem,
    options,
    answer,
    analysis,
  })
}

/**
 * 解析并校验导入文件。
 *
 * @param {File} file Excel 文件（.xlsx / .xls）
 * @param {Array} videos 视频列表（需包含 id、title、courseId）
 * @param {{ stage?: 'pre' | 'post' }} options 限定导入阶段（「导入本组」时使用）
 * @returns {Promise<{
 *   items: Array, errors: Array<{ rowNumber: number, messages: string[] }>,
 *   groups: Array<{ videoId: number, videoTitle: string, courseId: string, stage: string, questions: Array }>
 * }>}
 */
export async function parseQuestionFile(file, videos, options = {}) {
  const rows = await readTemplateSheet(file)
  const columns = mapColumns(rows[0] ?? [])

  const items = []
  const errors = []
  const seenKeys = new Set()

  rows.slice(1).forEach((row, index) => {
    const rowNumber = index + 2
    const isEmpty = row.every((cell) => !text(cell))
    if (isEmpty) return

    const rowErrors = []
    validateRow(row, columns, rowNumber, videos, seenKeys, rowErrors, items, options.stage)
    if (rowErrors.length) errors.push({ rowNumber, messages: rowErrors })
  })

  const groupMap = new Map()
  for (const item of items) {
    const key = `${item.videoId}|${item.stage}`
    if (!groupMap.has(key)) {
      groupMap.set(key, {
        videoId: item.videoId,
        videoTitle: item.videoTitle,
        courseId: item.courseId,
        stage: item.stage,
        questions: [],
      })
    }
    groupMap.get(key).questions.push({
      order: item.order,
      type: item.type,
      stem: item.stem,
      options: item.options,
      answer: item.answer,
      analysis: item.analysis,
    })
  }

  for (const group of groupMap.values()) {
    group.questions.sort((a, b) => a.order - b.order)
  }

  return { items, errors, groups: [...groupMap.values()] }
}
