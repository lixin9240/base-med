/**
 * 医护身份筛选兜底工具。
 * 约定与后端一致：1=医护；0=非医护（含未采集 null）。
 */

/** @param {unknown} value */
export function medicalStaffLabel(value) {
  if (value === null || value === undefined || value === '') return '未采集'
  return Number(value) === 1 ? '医护人员' : '非医护人员'
}

/**
 * @param {{ is_medical_staff?: unknown }} item
 * @param {unknown} filter 1 / 0 / '' / null / undefined
 */
export function matchesMedicalStaff(item, filter) {
  if (filter === undefined || filter === null || filter === '') return true
  const value = item?.is_medical_staff
  if (Number(filter) === 1) return Number(value) === 1
  return Number(value) !== 1
}

/**
 * 分页拉全量答题记录（仅带视频/结果等后端已支持的筛选）。
 * @param {(params: object) => Promise<{ list: any[], total: number }>} listFn
 * @param {{ videoId?: unknown, resultStatus?: unknown }} [filters]
 */
export async function fetchAllExamRecords(listFn, filters = {}) {
  const pageSize = 100
  let pageNo = 1
  let totalCount = Infinity
  const list = []

  while (list.length < totalCount) {
    const data = await listFn({
      page: pageNo,
      pageSize,
      videoId: filters.videoId,
      resultStatus: filters.resultStatus,
    })
    const chunk = Array.isArray(data.list) ? data.list : []
    totalCount = Number(data.total) || chunk.length
    list.push(...chunk)
    if (!chunk.length || chunk.length < pageSize) break
    pageNo += 1
    if (pageNo > 200) break
  }

  return list
}

function roundAvg(nums) {
  if (!nums.length) return null
  return Math.round(nums.reduce((sum, n) => sum + n, 0) / nums.length)
}

function itemPreRate(item) {
  const total = Number(item.pre_total) || 0
  if (!total) return null
  return ((Number(item.pre_correct) || 0) / total) * 100
}

function itemPostRate(item) {
  if (item.accuracy_rate !== null && item.accuracy_rate !== undefined && item.accuracy_rate !== '') {
    return Number(item.accuracy_rate)
  }
  const total = Number(item.post_total) || 0
  if (!total) return null
  return ((Number(item.post_correct) || 0) / total) * 100
}

/**
 * 用答题记录前端重算大盘答题相关指标（视频总数等仍用接口原值）。
 * @param {Record<string, unknown>} baseOverview
 * @param {Array<Record<string, unknown>>} records
 */
export function buildOverviewFromRecords(baseOverview, records) {
  const totalTimes = records.length
  const passTimes = records.filter((item) => item.result_status === 'pass').length
  const preRates = records.map(itemPreRate).filter((n) => n !== null)
  const postRates = records.map(itemPostRate).filter((n) => n !== null)
  const postAvg = roundAvg(postRates)

  return {
    ...baseOverview,
    answer_total_times: totalTimes,
    avg_accuracy: postAvg,
    pre_avg_accuracy: roundAvg(preRates),
    post_avg_accuracy: postAvg,
    pass_rate: totalTimes > 0 ? Math.round((passTimes / totalTimes) * 100) : null,
  }
}

/**
 * 用答题记录前端重算各视频答题指标，保留接口下发的视频元信息。
 * @param {Array<Record<string, unknown>>} videoRows
 * @param {Array<Record<string, unknown>>} records
 */
export function buildVideoStatsFromRecords(videoRows, records) {
  const byVideo = new Map()
  for (const item of records) {
    const id = Number(item.video_id)
    if (!byVideo.has(id)) byVideo.set(id, [])
    byVideo.get(id).push(item)
  }

  return videoRows.map((row) => {
    const items = byVideo.get(Number(row.video_id)) || []
    const attempts = items.length
    const passTimes = items.filter((item) => item.result_status === 'pass').length
    const preAvg = roundAvg(items.map(itemPreRate).filter((n) => n !== null))
    const postAvg = roundAvg(items.map(itemPostRate).filter((n) => n !== null))

    return {
      ...row,
      answer_times: attempts,
      avg_accuracy: postAvg,
      pre_avg_accuracy: preAvg,
      post_avg_accuracy: postAvg,
      pass_rate: attempts > 0 ? Math.round((passTimes / attempts) * 100) : null,
    }
  })
}
