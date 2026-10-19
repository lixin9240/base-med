export const QUESTION_TYPE_LABEL = {
  single: '单选题',
  multiple: '多选题',
  judge: '判断题',
}

const JUDGE_OPTIONS = ['正确', '错误']

export function optionLetter(index) {
  return String.fromCharCode(65 + index)
}

export function isAnswerCorrect(question, userAnswer) {
  const answer = question.answer
  if (userAnswer == null || userAnswer === '') return false
  if (question.type === 'multiple') {
    const picked = Array.isArray(userAnswer) ? [...userAnswer].sort() : []
    const expected = Array.isArray(answer) ? [...answer].sort() : []
    return picked.length > 0 && picked.length === expected.length && picked.every((v, i) => v === expected[i])
  }
  return String(userAnswer).toUpperCase() === String(answer).toUpperCase()
}

export function gradeQuiz(questions, answers, passPercent = 60) {
  let correct = 0
  const detail = questions.map((q, i) => {
    const userAnswer = answers[i]
    const ok = isAnswerCorrect(q, userAnswer)
    if (ok) correct += 1
    return {
      questionId: q.id,
      stem: q.stem,
      type: q.type,
      options: q.options,
      userAnswer,
      answer: q.answer,
      correct: ok,
      analysis: q.analysis || '',
    }
  })
  const total = questions.length
  const percent = total ? Math.round((correct / total) * 100) : 0
  return {
    correct,
    total,
    percent,
    passed: percent >= Number(passPercent || 0),
    detail,
  }
}

export function hasAnswer(value) {
  if (value == null || value === '') return false
  if (Array.isArray(value)) return value.length > 0
  return true
}
