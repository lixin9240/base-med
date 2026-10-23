<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  getAdminVideoDetail,
  listAdminCategories,
  listAdminVideos,
  updateAdminVideoStatus,
} from '@/api'
import { VIDEO_SOURCE } from '@/utils/video/constants'
import { findVideoByTitle } from '@/store/video/video'
import QuestionImportModal from '@/components/admin/videos/QuestionImportModal.vue'
import QuestionConfigDrawer from '@/components/admin/videos/QuestionConfigDrawer.vue'
import VideoFormModal from '@/components/admin/videos/VideoFormModal.vue'
import VideoShareLinkModal from '@/components/admin/videos/VideoShareLinkModal.vue'
import VideoDeleteModal from '@/components/admin/videos/VideoDeleteModal.vue'

const importOpen = ref(false)

/** 每页条数，对应接口的 page_size */
const PAGE_SIZE = 20

const videos = ref([])
const total = ref(0)
const page = ref(1)
const loading = ref(false)
const loadError = ref('')
const switchingId = ref(null)
const editLoadingId = ref(null)

/** 分类列表（视频表单下拉用），来自 2.7.1；保存时用它把分类名换成 category_id */
const categories = ref([])

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / PAGE_SIZE)))

const configOpen = ref(false)
const configVideo = ref(null)

const formOpen = ref(false)
const editingVideo = ref(null)

const shareOpen = ref(false)
const shareVideo = ref(null)

const deleteOpen = ref(false)
const deleteVideo = ref(null)

/** 题目配置 / 删除弹窗仍按标题匹配本地记录（本地题目数据）；找不到时用接口行兜底 */
function resolveLocalVideo(video) {
  return findVideoByTitle(video.title) ?? video
}

function countTone(count, total) {
  if (!total) return 'danger'
  if (count >= total) return 'success'
  return count > 0 ? 'warning' : 'danger'
}

function rateTone(rate) {
  if (rate === null || rate === undefined) return ''
  if (rate >= 80) return 'success'
  return rate >= 60 ? 'warning' : 'danger'
}

/** 把 2.2.1 接口返回的一条记录转成列表页展示需要的字段 */
function toVideoRow(item) {
  // 前后测共用一套题：优先取 question_count，兼容后端旧的 pre_count 字段
  const questionCount = Number(item.question_count ?? item.pre_count) || 0
  const questionTotal = Number(item.question_total ?? item.pre_total) || 0
  const rate = item.accuracy_rate

  return {
    id: item.id,
    title: item.title,
    category: item.category_name ?? '',
    cover: item.cover_url ?? '',
    speakerLabel: item.speaker
      ? [item.speaker, item.speaker_title].filter(Boolean).join(' · ')
      : '未指定',
    question: `${questionCount}/${questionTotal}`,
    questionCount,
    questionTone: countTone(questionCount, questionTotal),
    published: Number(item.status) === 1,
    attempts: `${Number(item.answer_times) || 0} 次`,
    accuracy: rate === null || rate === undefined ? '' : `${rate}%`,
    accuracyTone: rateTone(rate),
  }
}

async function refreshVideos() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await listAdminVideos({ page: page.value, pageSize: PAGE_SIZE })
    videos.value = data.list.map(toVideoRow)
    total.value = data.total
  } catch (error) {
    videos.value = []
    total.value = 0
    loadError.value = error?.message || '视频列表加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

function goPage(next) {
  page.value = next
  refreshVideos()
}

/** 把 2.2.2 详情接口的字段转成表单草稿字段 */
function toEditDraft(detail) {
  return {
    id: detail.id,
    courseId: '',
    title: detail.title ?? '',
    category: String(detail.category_name ?? ''),
    speaker: detail.speaker ?? '',
    speakerTitle: detail.speaker_title ?? '',
    speakerOrg: detail.speaker_org ?? '',
    intro: detail.brief_intro ?? '',
    source: Number(detail.source_type) === 2 ? VIDEO_SOURCE.UPLOAD : VIDEO_SOURCE.URL,
    videoUrl: detail.video_url ?? '',
    cover: detail.cover_url ?? '',
    articleBody: detail.text_key_points ?? '',
    published: Number(detail.status) === 1,
    order: Number(detail.sort_order) || 1,
  }
}

async function loadCategories() {
  try {
    categories.value = await listAdminCategories()
  } catch (error) {
    if (!loadError.value) loadError.value = error?.message || '分类列表加载失败，请稍后重试'
  }
}

onMounted(() => {
  refreshVideos()
  loadCategories()
})

function openConfig(video) {
  // apiId 是 2.2.1 接口行的真实视频 id，题目接口（2.3.x）必须用它；
  // id 保留本地记录 id 供抽屉内其它本地逻辑使用
  configVideo.value = { ...resolveLocalVideo(video), apiId: video.id }
  configOpen.value = true
}

function openCreate() {
  editingVideo.value = null
  formOpen.value = true
}

async function openEdit(video) {
  if (editLoadingId.value !== null) return

  editLoadingId.value = video.id
  loadError.value = ''
  try {
    const detail = await getAdminVideoDetail(video.id)
    editingVideo.value = toEditDraft(detail)
    formOpen.value = true
  } catch (error) {
    loadError.value = error?.message || '视频详情加载失败，请稍后重试'
  } finally {
    editLoadingId.value = null
  }
}

function openShare(video) {
  // 分享链接接口（2.12）需要真实视频 id，直接用列表接口返回的行
  shareVideo.value = video
  shareOpen.value = true
}

function openDelete(video) {
  // apiId 是 2.2.1 接口行的真实视频 id，删除接口（2.2.6）必须用它；id 保留本地记录 id
  deleteVideo.value = {
    ...resolveLocalVideo(video),
    apiId: video.id,
    questionCount: video.questionCount,
  }
  deleteOpen.value = true
}

async function togglePublished(video) {
  if (switchingId.value !== null) return

  const nextStatus = video.published ? 0 : 1
  switchingId.value = video.id
  try {
    await updateAdminVideoStatus({ id: video.id, status: nextStatus })
    video.published = nextStatus === 1
  } catch (error) {
    loadError.value = error?.message || '状态切换失败，请稍后重试'
  } finally {
    switchingId.value = null
  }
}
</script>

<template>
  <section class="video-page">
    <!-- 页头 -->
    <header class="video-page__head">
      <div class="video-page__intro">
        <h1 class="video-page__title">视频管理</h1>
        <p class="video-page__desc">用户端免登录，点开就能看。这里维护视频、文字要点和题目。</p>
      </div>

      <div class="video-page__actions">
        <button type="button" class="btn btn--secondary" @click="importOpen = true">
          <svg
            class="btn__icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M12 15V4m0 0L7.5 8.5M12 4l4.5 4.5" />
            <path d="M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17" />
          </svg>
          批量导入题目
        </button>

        <button type="button" class="btn btn--primary" @click="openCreate">
          <svg
            class="btn__icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            aria-hidden="true"
          >
            <path d="M12 5v14M5 12h14" />
          </svg>
          新增视频
        </button>
      </div>
    </header>

    <!-- 加载中 / 加载失败提示 -->
    <div v-if="loading" class="video-banner">正在加载视频列表…</div>
    <div v-else-if="loadError" class="video-banner video-banner--error">
      <span>{{ loadError }}</span>
      <button type="button" class="link-btn" @click="refreshVideos">重试</button>
    </div>

    <!-- 视频列表 -->
    <div v-if="videos.length" class="video-table">
      <div class="video-table__head">
        <span class="col col--video">视频</span>
        <span class="col col--quiz">题目</span>
        <span class="col col--status">状态</span>
        <span class="col col--stat">答题</span>
        <span class="col col--ops">操作</span>
      </div>

      <div v-for="video in videos" :key="video.id" class="video-row">
        <!-- 视频信息 -->
        <div class="col col--video">
          <div class="video-cell">
            <span class="video-cell__thumb">
              <img v-if="video.cover" :src="video.cover" :alt="video.title" />
              <svg
                v-else
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <rect x="3" y="7" width="12" height="10" rx="2.5" />
                <path d="m15 10.5 6-3.2v9.4l-6-3.2" />
              </svg>
            </span>
            <div class="video-cell__info">
              <h2 class="video-cell__title">{{ video.title }}</h2>
              <div class="video-cell__meta">
                <span class="badge badge--category">{{ video.category }}</span>
                <span class="video-cell__speaker">{{ video.speakerLabel }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 题目 -->
        <div class="col col--quiz">
          <span class="badge" :class="`badge--${video.questionTone}`">
            题目 {{ video.question }}
          </span>
        </div>

        <!-- 状态 -->
        <div class="col col--status">
          <button
            type="button"
            class="status-toggle"
            role="switch"
            :aria-checked="video.published"
            :aria-label="video.published ? '已上架，点击下架' : '已下架，点击上架'"
            @click="togglePublished(video)"
          >
            <span class="status" :class="{ 'status--off': !video.published }">
              <span class="status__dot"></span>
              {{ video.published ? '已上架' : '已下架' }}
            </span>
            <span class="switch" :class="{ 'switch--on': video.published }">
              <span class="switch__knob"></span>
            </span>
          </button>
        </div>

        <!-- 答题 -->
        <div class="col col--stat">
          <span class="stat__count">{{ video.attempts }}</span>
          <template v-if="video.accuracy">
            <span class="stat__separator">·</span>
            <span class="stat__accuracy" :class="`stat__accuracy--${video.accuracyTone}`">
              {{ video.accuracy }}
            </span>
          </template>
        </div>

        <!-- 操作 -->
        <div class="col col--ops">
          <button type="button" class="link-btn" @click="openConfig(video)">题目配置</button>
          <button
            type="button"
            class="link-btn"
            :disabled="editLoadingId === video.id"
            @click="openEdit(video)"
          >
            {{ editLoadingId === video.id ? '加载中…' : '编辑' }}
          </button>
          <button type="button" class="link-btn" @click="openShare(video)">生成视频链接</button>
          <button type="button" class="link-btn link-btn--danger" @click="openDelete(video)">
            删除
          </button>
        </div>
      </div>
    </div>

    <p v-else-if="!loading && !loadError" class="video-banner">暂无视频</p>

    <!-- 分页 -->
    <div v-if="videos.length" class="video-pager">
      <span class="video-pager__total">共 {{ total }} 条</span>
      <button
        type="button"
        class="link-btn"
        :disabled="page <= 1 || loading"
        @click="goPage(page - 1)"
      >
        上一页
      </button>
      <span class="video-pager__page">第 {{ page }} / {{ totalPages }} 页</span>
      <button
        type="button"
        class="link-btn"
        :disabled="page >= totalPages || loading"
        @click="goPage(page + 1)"
      >
        下一页
      </button>
    </div>

    <QuestionImportModal :open="importOpen" @close="importOpen = false" @imported="refreshVideos" />

    <QuestionConfigDrawer
      :open="configOpen"
      :video="configVideo"
      @close="configOpen = false"
      @changed="refreshVideos"
    />

    <VideoFormModal
      :open="formOpen"
      :video="editingVideo"
      :categories="categories"
      @close="formOpen = false"
      @saved="refreshVideos"
    />

    <VideoShareLinkModal :open="shareOpen" :video="shareVideo" @close="shareOpen = false" />

    <VideoDeleteModal
      :open="deleteOpen"
      :video="deleteVideo"
      @close="deleteOpen = false"
      @deleted="refreshVideos"
    />
  </section>
</template>

<style scoped>
.video-page {
  display: flex;
  flex-direction: column;
  gap: var(--sp-5);
}

/* ===== 加载 / 错误 / 空态提示 ===== */
.video-banner {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  padding: var(--sp-4);
  margin: 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
}

.video-banner--error {
  color: var(--danger-text);
  background: var(--danger-bg);
  border-color: rgb(155 28 49 / 0.25);
}

/* ===== 分页 ===== */
.video-pager {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.video-pager__total {
  margin-right: auto;
}

.link-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* ===== 页头 ===== */
.video-page__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--sp-4);
  flex-wrap: wrap;
}

.video-page__intro {
  display: flex;
  flex-direction: column;
  gap: var(--sp-1);
}

.video-page__title {
  margin: 0;
  font-size: var(--fs-2xl);
  font-weight: var(--fw-bold);
  color: var(--text-primary);
}

.video-page__desc {
  margin: 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.video-page__actions {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
}

/* ===== 按钮 ===== */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  height: 40px;
  padding: 0 var(--sp-5);
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  border: 1px solid transparent;
  border-radius: var(--r-md);
  cursor: pointer;
  transition:
    background var(--t-fast),
    box-shadow var(--t-fast),
    transform var(--t-fast);
}

.btn__icon {
  width: 16px;
  height: 16px;
}

.btn--secondary {
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border-color: var(--border);
  box-shadow: var(--shadow-sm);
}

.btn--secondary:hover {
  background: var(--bg-muted);
}

.btn--primary {
  color: #fff;
  background: linear-gradient(
    165deg,
    var(--brand-primary-hover) 0%,
    var(--brand-primary) 55%,
    var(--brand-primary-active) 100%
  );
  box-shadow: 0 8px 20px var(--brand-glow);
}

.btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px var(--brand-glow);
}

/* ===== 列表卡片 ===== */
.video-table {
  display: flex;
  flex-direction: column;
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.video-table__head,
.video-row {
  display: flex;
  align-items: center;
  gap: var(--sp-4);
  padding: var(--sp-4) var(--sp-5);
}

.video-table__head {
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border-bottom: 1px solid var(--border);
}

.video-row {
  border-bottom: 1px solid var(--border-light);
  transition: background var(--t-fast);
}

.video-row:last-child {
  border-bottom: none;
}

.video-row:hover {
  background: var(--brand-tint);
}

/* 列宽 */
.col {
  display: flex;
  align-items: center;
  min-width: 0;
}

.col--video {
  flex: 2.4;
}

.col--quiz {
  flex: 1;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--sp-1);
}

.col--status {
  flex: 1.2;
  gap: var(--sp-3);
}

.col--stat {
  flex: 0.9;
  gap: 6px;
  font-size: var(--fs-sm);
}

.col--ops {
  flex: 1.9;
  justify-content: flex-end;
  gap: var(--sp-4);
}

/* ===== 视频信息 ===== */
.video-cell {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  min-width: 0;
}

.video-cell__thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 64px;
  height: 48px;
  color: var(--brand-primary);
  background: var(--brand-tint);
  border-radius: var(--r-md);
  overflow: hidden;
}

.video-cell__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.video-cell__thumb svg {
  width: 22px;
  height: 22px;
}

.video-cell__info {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
  min-width: 0;
}

.video-cell__title {
  margin: 0;
  font-size: var(--fs-base);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.video-cell__meta {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  min-width: 0;
}

.video-cell__speaker {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ===== 标签 ===== */
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  padding: 3px 10px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  border-radius: var(--r-full);
  white-space: nowrap;
}

.badge--category {
  color: var(--info-text);
  background: var(--info-bg);
}

.badge--success {
  color: var(--success-text);
  background: var(--success-bg);
}

.badge--warning {
  color: var(--warning-text);
  background: var(--warning-bg);
}

.badge--danger {
  color: var(--danger-text);
  background: var(--danger-bg);
}

/* ===== 状态 ===== */
.status-toggle {
  display: inline-flex;
  align-items: center;
  gap: var(--sp-3);
  padding: 0;
  background: none;
  border: none;
  cursor: pointer;
  border-radius: var(--r-full);
}

.status-toggle:focus-visible {
  outline: 2px solid var(--brand-primary-hover);
  outline-offset: 4px;
}

.status-toggle:hover .switch:not(.switch--on) {
  background: #cfdcd4;
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  font-size: var(--fs-xs);
  font-weight: var(--fw-medium);
  color: var(--success-text);
  background: var(--success-bg);
  border-radius: var(--r-full);
  white-space: nowrap;
}

.status--off {
  color: var(--text-secondary);
  background: #eef2f0;
}

.status__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

/* 开关（纯展示） */
.switch {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
  width: 40px;
  height: 22px;
  padding: 0;
  background: #dfe6e1;
  border: none;
  border-radius: var(--r-full);
  transition: background var(--t-fast);
}

.switch--on {
  background: var(--brand-primary-hover);
}

.switch__knob {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 18px;
  height: 18px;
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgb(27 67 50 / 0.2);
  transition: transform var(--t-fast);
}

.switch--on .switch__knob {
  transform: translateX(18px);
}

/* ===== 答题数据 ===== */
.stat__count {
  color: var(--text-secondary);
}

.stat__separator {
  color: var(--text-disabled);
}

.stat__accuracy {
  font-weight: var(--fw-semibold);
}

.stat__accuracy--success {
  color: var(--success);
}

.stat__accuracy--warning {
  color: var(--warning-text);
}

/* ===== 操作链接 ===== */
.link-btn {
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  background: none;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  transition: color var(--t-fast);
}

.link-btn:hover {
  color: var(--brand-primary-active);
  text-decoration: underline;
}

.link-btn--danger {
  color: var(--danger-text);
}

.link-btn--danger:hover {
  color: var(--danger);
}

/* ===== 窄屏横向滚动 ===== */
@media (max-width: 1100px) {
  .video-table {
    overflow-x: auto;
  }

  .video-table__head,
  .video-row {
    min-width: 1000px;
  }
}
</style>
