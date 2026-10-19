import { TEMPLATE_SHEET } from './questionImport'

const HEADERS = [
  '视频标题*',
  '题目用途*',
  '题序*',
  '题型*',
  '题干*',
  '选项A*',
  '选项B*',
  '选项C',
  '选项D',
  '选项E',
  '选项F',
  '正确答案*',
  '答案解析',
]

const TYPE_LABEL = {
  single: '单选题',
  multiple: '多选题',
  judge: '判断题',
}

const STAGE_LABEL = {
  pre: '前测',
  post: '后测',
}

function safeFileName(value) {
  return String(value ?? '').replace(/[\\/:*?"<>|]/g, '_')
}

/**
 * 生成与导入模板同结构的二维表数据。
 */
export function buildQuestionExportRows({ videoTitle, stage, questions }) {
  const rows = [HEADERS]

  for (const question of questions) {
    const options = Array.isArray(question.options) ? [...question.options] : []
    while (options.length < 6) options.push('')

    rows.push([
      videoTitle,
      STAGE_LABEL[stage] ?? stage,
      question.order,
      TYPE_LABEL[question.type] ?? question.type,
      question.stem,
      ...options.slice(0, 6),
      Array.isArray(question.answer) ? question.answer.join('') : (question.answer ?? ''),
      question.analysis ?? '',
    ])
  }

  return rows
}

/**
 * 将某一组题目导出为与导入模板同结构的 Excel 文件。
 */
export async function exportQuestionGroup({ videoTitle, stage, questions }) {
  const XLSX = await import('xlsx')
  const rows = buildQuestionExportRows({ videoTitle, stage, questions })

  const sheet = XLSX.utils.aoa_to_sheet(rows)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, sheet, TEMPLATE_SHEET)

  const name = `${safeFileName(videoTitle)}_${STAGE_LABEL[stage] ?? stage}_题目.xlsx`
  XLSX.writeFile(workbook, name)
}
