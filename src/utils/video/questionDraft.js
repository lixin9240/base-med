export const STEM_MAX = 300
export const ANALYSIS_MAX = 500
export const MIN_OPTIONS = 2
export const MAX_OPTIONS = 6

export function createQuestionDraft(type = 'single') {
  return {
    type,
    stem: '',
    options: defaultOptions(type),
    correct: [],
    analysis: '',
  }
}

function defaultOptions(type) {
  if (type === 'judge') return ['正确', '错误']
  return ['', '', '', '']
}

function answerToIndexes(answer, optionCount) {
  const list = Array.isArray(answer) ? answer : [answer]
  return list
    .map(
      (letter) =>
        String(letter ?? '')
          .toUpperCase()
          .charCodeAt(0) - 65,
    )
    .filter((index) => Number.isInteger(index) && index >= 0 && index < optionCount)
}

export function questionToDraft(question) {
  const type = question?.type ?? 'single'

  if (type === 'judge') {
    return {
      type,
      stem: question?.stem ?? '',
      options: ['正确', '错误'],
      correct: answerToIndexes(question?.answer, 2),
      analysis: question?.analysis ?? '',
    }
  }

  const options = Array.isArray(question?.options) ? [...question.options] : []
  while (options.length < 4) options.push('')

  return {
    type,
    stem: question?.stem ?? '',
    options,
    correct: answerToIndexes(question?.answer, options.length),
    analysis: question?.analysis ?? '',
  }
}

export function draftToQuestion(draft) {
  const type = draft.type
  const options =
    type === 'judge' ? ['正确', '错误'] : draft.options.map((option) => String(option ?? '').trim())
  const letters = [...draft.correct]
    .sort((a, b) => a - b)
    .map((index) => String.fromCharCode(65 + index))

  return {
    type,
    stem: String(draft.stem ?? '').trim(),
    options,
    answer: type === 'multiple' ? letters : (letters[0] ?? ''),
    analysis: String(draft.analysis ?? '').trim(),
  }
}

/**
 * 把本地题目结构（draftToQuestion 的产物）转成接口 2.3.2 / 2.3.3 请求体的题目字段。
 * options 字符串数组 → [{ key, content }]；answer 字母 / 字母数组 → correct_answer 字符串。
 */
export function questionToApiPayload(question) {
  const type = question?.type ?? 'single'
  const options = (question?.options ?? []).map((content, index) => ({
    key: String.fromCharCode(65 + index),
    content,
  }))

  let correctAnswer
  if (type === 'multiple') {
    correctAnswer = (Array.isArray(question.answer) ? question.answer : [question.answer])
      .filter((item) => item != null && item !== '')
      .sort()
      .join(',')
  } else if (type === 'judge') {
    const index =
      String(question.answer ?? '')
        .toUpperCase()
        .charCodeAt(0) - 65
    correctAnswer = options[index]?.content ?? question.answer ?? ''
  } else {
    correctAnswer = question.answer ?? ''
  }

  return {
    type,
    stem: question?.stem ?? '',
    options,
    correct_answer: correctAnswer,
    analysis: question?.analysis ?? '',
  }
}

/**
 * 校验表单草稿，返回字段级错误；空对象表示通过。
 */
export function validateQuestionDraft(draft) {
  const errors = {}
  const stem = String(draft?.stem ?? '').trim()
  const analysis = String(draft?.analysis ?? '').trim()
  const correct = draft?.correct ?? []

  if (!stem) errors.stem = '请填写题干'
  else if (stem.length > STEM_MAX) errors.stem = `题干不能超过 ${STEM_MAX} 字`

  if (analysis.length > ANALYSIS_MAX) {
    errors.analysis = `答案解析不能超过 ${ANALYSIS_MAX} 字`
  }

  if (draft?.type === 'judge') {
    if (correct.length !== 1) errors.answer = '判断题需选择 1 个正确答案'
    return errors
  }

  const options = (draft?.options ?? []).map((option) => String(option ?? '').trim())
  let lastFilled = -1
  options.forEach((option, index) => {
    if (option) lastFilled = index
  })
  const filled = options.slice(0, lastFilled + 1)

  if (filled.filter(Boolean).length < MIN_OPTIONS) {
    errors.options = '至少填写 A、B 两个选项'
    return errors
  }
  if (filled.some((option) => !option)) {
    errors.options = '选项需从 A 起连续填写，不能留空'
    return errors
  }
  if (correct.some((index) => index < 0 || index >= filled.length)) {
    errors.answer = '正确答案对应的选项未填写'
    return errors
  }

  if (draft.type === 'single' && correct.length !== 1) {
    errors.answer = '单选题需选择 1 个正确答案'
  }
  if (draft.type === 'multiple' && correct.length < 2) {
    errors.answer = '多选题至少选择 2 个正确答案'
  }

  return errors
}
