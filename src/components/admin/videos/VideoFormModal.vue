<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { completeAdminVideoUpload, createAdminVideo, initAdminVideoUpload, updateAdminVideo } from '@/api'
import {
  VIDEO_CATEGORIES,
  VIDEO_INTRO_MAX,
  VIDEO_MAX_SIZE_MB,
  VIDEO_SOURCE,
  VIDEO_TITLE_MAX,
} from '@/utils/video/constants'
import { createVideoDraft, validateVideoDraft } from '@/utils/video/videoForm'
import {
  uploadCoverFile,
  uploadVideoFile,
  validateCoverFile,
  validateVideoFile,
} from '@/utils/video/upload'
import { listVideos } from '@/store/video/video'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  video: {
    type: Object,
    default: null,
  },
  /** 分类列表（来自 2.7.1），[{ id, name }] */
  categories: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'saved'])

const draft = reactive(createVideoDraft())
const errors = ref({})
const saving = ref(false)
const uploadingVideo = ref(false)
const uploadingCover = ref(false)
const uploadError = ref('')
const videoFileName = ref('')
/** 2.2.7 初始化返回的上传任务（oss_upload_id 等），传输完成后用于 2.2.8 完成上传 */
const uploadTask = ref(null)
const videoInput = ref(null)
const coverInput = ref(null)

const isEdit = computed(() => Boolean(props.video?.id))
const modalTitle = computed(() => (isEdit.value ? '编辑视频' : '新增视频'))
const isUpload = computed(() => draft.source === VIDEO_SOURCE.UPLOAD)
/** 分片传输通道尚未就绪（文档未定义分片端点）：新增时禁用本地上传，编辑重选文件走 2.2.7 初始化 + 2.2.8 完成上传 */
const canUpload = computed(() => isEdit.value)
const titleCount = computed(() => draft.title.length)
const introCount = computed(() => draft.intro.length)
const submitLabel = computed(() => (saving.value ? '保存中…' : '保存'))

/** 分类下拉选项：优先用 2.7.1 接口返回的分类名，接口未就绪时退回本地常量 */
const categoryOptions = computed(() =>
  props.categories?.length ? props.categories.map((item) => item.name) : VIDEO_CATEGORIES,
)

/** 编辑回填的分类名不在列表里时的提示 */
const categoryMissing = computed(
  () => Boolean(draft.category) && !categoryOptions.value.includes(draft.category),
)

function categoryIdOf(name) {
  const matched = props.categories?.find((item) => item.name === name)
  return matched ? matched.id : null
}

watch(
  () => props.open,
  (open) => {
    if (open) reset()
  },
  { immediate: true },
)

function nextOrder() {
  return listVideos().reduce((max, video) => Math.max(max, Number(video.order) || 0), 0) + 1
}

function reset() {
  const next = createVideoDraft(props.video)
  if (!props.video) next.order = nextOrder()
  Object.assign(draft, next)
  errors.value = {}
  uploadError.value = ''
  videoFileName.value = ''
  uploadTask.value = null
  saving.value = false
  uploadingVideo.value = false
  uploadingCover.value = false
  if (videoInput.value) videoInput.value.value = ''
  if (coverInput.value) coverInput.value.value = ''
}

function clearError(key) {
  if (!errors.value[key]) return
  const next = { ...errors.value }
  delete next[key]
  errors.value = next
}

function selectSource(source) {
  if (source === VIDEO_SOURCE.UPLOAD && !canUpload.value) return
  if (draft.source === source) return
  draft.source = source
  uploadError.value = ''

  if (source === VIDEO_SOURCE.UPLOAD && !draft.videoUrl.startsWith('blob:')) {
    draft.videoUrl = ''
    videoFileName.value = ''
  }
  if (source === VIDEO_SOURCE.URL && draft.videoUrl.startsWith('blob:')) {
    draft.videoUrl = ''
    videoFileName.value = ''
  }
}

async function onVideoFile(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return

  const message = validateVideoFile(file)
  if (message) {
    uploadError.value = message
    return
  }

  uploadingVideo.value = true
  uploadError.value = ''
  try {
    // 2.2.7 初始化分片上传：编辑重新上传带 video_id；已有未完成任务时后端返回原 oss_upload_id（断点续传）
    uploadTask.value = await initAdminVideoUpload({
      file_name: file.name,
      file_size: file.size,
      video_id: isEdit.value ? props.video.id : undefined,
    })
    draft.videoUrl = await uploadVideoFile(file)
    videoFileName.value = file.name
    clearError('videoUrl')
    // 2.2.8 完成上传：通知后端合并分片并触发转码；转码异步，状态经列表/详情的 transcode_status 查看
    if (uploadTask.value?.oss_upload_id) {
      await completeAdminVideoUpload({
        oss_upload_id: uploadTask.value.oss_upload_id,
        video_id: props.video.id,
      })
    }
  } catch (error) {
    uploadError.value = error?.message || '视频上传失败，请重试'
  } finally {
    uploadingVideo.value = false
  }
}

async function onCoverFile(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return

  const message = validateCoverFile(file)
  if (message) {
    uploadError.value = message
    return
  }

  uploadingCover.value = true
  uploadError.value = ''
  try {
    draft.cover = await uploadCoverFile(file)
  } catch (error) {
    uploadError.value = error?.message || '封面上传失败，请重试'
  } finally {
    uploadingCover.value = false
  }
}

function stepOrder(delta) {
  const value = Math.round(Number(draft.order) || 1)
  draft.order = Math.max(1, value + delta)
}

function clearVideoFile() {
  draft.videoUrl = ''
  videoFileName.value = ''
  clearError('videoUrl')
}

async function submit() {
  if (saving.value || uploadingVideo.value || uploadingCover.value) return

  const result = validateVideoDraft(draft)
  errors.value = result.errors
  if (!result.valid) return

  saving.value = true
  uploadError.value = ''
  try {
    // 新增和编辑都要提交真实的 category_id
    const categoryId = categoryIdOf(draft.category)
    if (categoryId === null) {
      errors.value = { ...errors.value, category: '该分类不在分类列表中，请重新选择' }
      return
    }

    // 2.2.3 新增 / 2.2.4 编辑共用同一组字段
    const payload = {
      title: draft.title.trim(),
      category_id: categoryId,
      speaker: draft.speaker.trim(),
      speaker_title: draft.speakerTitle.trim(),
      speaker_org: draft.speakerOrg.trim(),
      brief_intro: draft.intro.trim(),
      source_type: draft.source === VIDEO_SOURCE.UPLOAD ? 2 : 1,
      video_url: draft.videoUrl.trim(),
      cover_url: draft.cover,
      text_key_points: draft.articleBody,
      status: draft.published ? 1 : 0,
      sort_order: Number(draft.order) || 1,
    }

    if (isEdit.value) {
      await updateAdminVideo({ id: props.video.id, ...payload })
    } else {
      await createAdminVideo(payload)
    }

    // 不再写本地记录，列表以接口为准，由父组件重新拉取
    emit('saved', null)
    emit('close')
  } catch (error) {
    uploadError.value = error?.message || '保存失败，请重试'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div v-if="open" class="video-form" @click.self="$emit('close')">
    <div
      class="video-form__dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="video-form-title"
    >
      <header class="video-form__head">
        <h2 id="video-form-title" class="video-form__title">{{ modalTitle }}</h2>
        <button type="button" class="video-form__close" aria-label="关闭" @click="$emit('close')">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            aria-hidden="true"
          >
            <path d="m6 6 12 12M18 6 6 18" />
          </svg>
        </button>
      </header>

      <form class="video-form__body" @submit.prevent="submit">
        <!-- 标题 + 分类 -->
        <div class="form-row">
          <label class="field field--grow">
            <span class="field__label"><em class="req">*</em>视频标题</span>
            <span class="field__control">
              <input
                v-model="draft.title"
                type="text"
                :maxlength="VIDEO_TITLE_MAX"
                placeholder="用户端列表展示的标题"
              />
              <span class="field__counter"> {{ titleCount }} / {{ VIDEO_TITLE_MAX }} </span>
            </span>
            <p v-if="errors.title" class="field__error">{{ errors.title }}</p>
          </label>

          <label class="field field--category">
            <span class="field__label"><em class="req">*</em>分类</span>
            <select v-model="draft.category">
              <option v-for="name in categoryOptions" :key="name" :value="name">
                {{ name }}
              </option>
            </select>
            <p v-if="errors.category" class="field__error">{{ errors.category }}</p>
            <p v-else-if="categoryMissing" class="field__error">
              该分类不在分类列表中，请重新选择
            </p>
          </label>
        </div>

        <!-- 主讲人信息 -->
        <div class="form-row">
          <label class="field field--grow">
            <span class="field__label"><em class="req">*</em>主讲人</span>
            <input v-model="draft.speaker" type="text" placeholder="如 郭军" />
            <p v-if="errors.speaker" class="field__error">{{ errors.speaker }}</p>
          </label>

          <label class="field field--grow">
            <span class="field__label">职称</span>
            <input v-model="draft.speakerTitle" type="text" placeholder="如 主任医师" />
          </label>

          <label class="field field--grow">
            <span class="field__label">单位</span>
            <input v-model="draft.speakerOrg" type="text" placeholder="如 四川大学华西医院" />
          </label>
        </div>

        <!-- 一句话简介 -->
        <label class="field">
          <span class="field__label"><em class="req">*</em>一句话简介</span>
          <span class="field__control field__control--area">
            <textarea
              v-model="draft.intro"
              :maxlength="VIDEO_INTRO_MAX"
              rows="3"
              placeholder="列表里展示的一句话，说清这条视频讲什么"
            />
            <span class="field__counter field__counter--area">
              {{ introCount }} / {{ VIDEO_INTRO_MAX }}
            </span>
          </span>
          <p v-if="errors.intro" class="field__error">{{ errors.intro }}</p>
        </label>

        <!-- 视频来源 -->
        <div class="field">
          <span class="field__label"><em class="req">*</em>视频来源</span>
          <div class="radio-group">
            <label class="radio" :class="{ 'radio--on': !isUpload }">
              <input
                type="radio"
                name="video-source"
                :value="VIDEO_SOURCE.URL"
                :checked="!isUpload"
                @change="selectSource(VIDEO_SOURCE.URL)"
              />
              <span class="radio__dot"></span>
              填写视频链接
            </label>
            <label class="radio" :class="{ 'radio--on': isUpload, 'radio--disabled': !canUpload }">
              <input
                type="radio"
                name="video-source"
                :value="VIDEO_SOURCE.UPLOAD"
                :checked="isUpload"
                :disabled="!canUpload"
                @change="selectSource(VIDEO_SOURCE.UPLOAD)"
              />
              <span class="radio__dot"></span>
              本地上传
            </label>
          </div>
          <p v-if="!canUpload" class="field__hint">
            分片传输通道尚未就绪，新增视频暂只支持填写视频链接。
          </p>
        </div>

        <!-- 视频链接 -->
        <label v-if="!isUpload" class="field">
          <span class="field__label"><em class="req">*</em>视频链接</span>
          <input
            v-model="draft.videoUrl"
            type="text"
            placeholder="https://.../video.mp4（正式环境填 OSS 直链）"
          />
          <p v-if="errors.videoUrl" class="field__error">{{ errors.videoUrl }}</p>
        </label>

        <!-- 本地视频上传 -->
        <div v-else class="field">
          <span class="field__label"><em class="req">*</em>上传视频</span>
          <input
            ref="videoInput"
            class="sr-only"
            type="file"
            accept="video/*"
            @change="onVideoFile"
          />

          <div v-if="!draft.videoUrl" class="dropzone">
            <svg
              class="dropzone__icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M12 16V5m0 0L7.5 9.5M12 5l4.5 4.5" />
              <path d="M4 16v2.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V16" />
            </svg>
            <button
              type="button"
              class="dropzone__btn"
              :disabled="uploadingVideo"
              @click="videoInput?.click()"
            >
              {{ uploadingVideo ? '上传中…' : '选择视频文件' }}
            </button>
            <span class="dropzone__hint">
              支持 mp4 / webm / ogg / mov，单个不超过 {{ VIDEO_MAX_SIZE_MB }}MB
            </span>
          </div>

          <div v-else class="file-chip">
            <svg
              class="file-chip__icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <rect x="3" y="7" width="12" height="10" rx="2.5" />
              <path d="m15 10.5 6-3.2v9.4l-6-3.2" />
            </svg>
            <span class="file-chip__name">{{ videoFileName || '已选择视频' }}</span>
            <button type="button" class="link-btn" @click="clearVideoFile">重新选择</button>
          </div>

          <p class="field__hint">
            未配置上传服务时仅生成浏览器本地预览地址，正式环境请配置 VITE_UPLOAD_ENDPOINT。
          </p>
          <p v-if="errors.videoUrl" class="field__error">{{ errors.videoUrl }}</p>
        </div>

        <!-- 封面图 -->
        <div class="field">
          <span class="field__label">封面图</span>
          <div class="cover-row">
            <div class="cover-thumb">
              <img v-if="draft.cover" :src="draft.cover" alt="封面预览" />
              <svg
                v-else
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <rect x="3" y="7" width="12" height="10" rx="2.5" />
                <path d="m15 10.5 6-3.2v9.4l-6-3.2" />
              </svg>
            </div>

            <div class="cover-actions">
              <input
                ref="coverInput"
                class="sr-only"
                type="file"
                accept="image/*"
                @change="onCoverFile"
              />
              <div class="cover-actions__row">
                <button
                  type="button"
                  class="btn btn--secondary btn--sm"
                  :disabled="uploadingCover"
                  @click="coverInput?.click()"
                >
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
                    <path d="M4 16.5V19a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2.5" />
                    <path d="M12 15V4m0 0L7.5 8.5M12 4l4.5 4.5" />
                  </svg>
                  {{ uploadingCover ? '上传中…' : '上传图片' }}
                </button>
                <button
                  v-if="draft.cover"
                  type="button"
                  class="link-btn link-btn--muted"
                  @click="draft.cover = ''"
                >
                  移除
                </button>
              </div>
              <p class="field__hint">不传也能用：列表会用分类图标占位。</p>
            </div>
          </div>
        </div>

        <!-- 文字要点 -->
        <label class="field">
          <span class="field__label">文字要点</span>
          <textarea
            v-model="draft.articleBody"
            class="article-input"
            rows="6"
            placeholder="可选。给不方便看视频的人一段文字说明。段落前用【小标题】，用 **文字** 表示加粗。"
          />
        </label>

        <!-- 上架状态 + 排序号 -->
        <div class="form-row">
          <label class="field field--grow">
            <span class="field__label"><em class="req">*</em>上架状态</span>
            <select v-model="draft.published">
              <option :value="true">已上架（用户可见）</option>
              <option :value="false">已下架（用户不可见）</option>
            </select>
          </label>

          <div class="field field--order">
            <span class="field__label">排序号</span>
            <div class="stepper">
              <button type="button" aria-label="减小排序号" @click="stepOrder(-1)">−</button>
              <input v-model.number="draft.order" type="number" min="1" step="1" />
              <button type="button" aria-label="增大排序号" @click="stepOrder(1)">+</button>
            </div>
            <p v-if="errors.order" class="field__error">{{ errors.order }}</p>
          </div>
        </div>

        <p v-if="uploadError" class="form-error">{{ uploadError }}</p>

        <footer class="video-form__foot">
          <button type="button" class="btn btn--secondary" @click="$emit('close')">取消</button>
          <button
            type="submit"
            class="btn btn--primary"
            :disabled="saving || uploadingVideo || uploadingCover"
          >
            {{ submitLabel }}
          </button>
        </footer>
      </form>
    </div>
  </div>
</template>

<style scoped>
.video-form {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--sp-5);
  background: rgb(8 28 21 / 0.45);
  backdrop-filter: blur(2px);
}

.video-form__dialog {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 860px;
  max-height: calc(100vh - 48px);
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

/* ===== 标题栏 ===== */
.video-form__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--sp-4);
  padding: var(--sp-5) var(--sp-6) var(--sp-2);
}

.video-form__title {
  margin: 0;
  font-size: var(--fs-xl);
  font-weight: var(--fw-semibold);
  color: var(--text-primary);
}

.video-form__close {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  padding: 0;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.video-form__close:hover {
  color: var(--text-primary);
  background: var(--bg-muted);
}

.video-form__close svg {
  width: 20px;
  height: 20px;
}

/* ===== 表单主体 ===== */
.video-form__body {
  display: flex;
  flex-direction: column;
  gap: var(--sp-4);
  padding: var(--sp-3) var(--sp-6) 0;
  overflow-y: auto;
}

.form-row {
  display: flex;
  align-items: flex-start;
  gap: var(--sp-4);
}

.field {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
  min-width: 0;
}

.field--grow {
  flex: 1;
}

.field--category {
  flex: 0 0 220px;
}

.field--order {
  flex: 0 0 220px;
}

.field__label {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--text-primary);
}

.req {
  font-style: normal;
  color: var(--danger);
}

.field__control {
  position: relative;
  display: block;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  padding: 0 var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  outline: none;
  transition:
    border-color var(--t-fast),
    box-shadow var(--t-fast);
}

.field input,
.field select {
  height: 42px;
}

.field textarea {
  padding: var(--sp-3);
  line-height: 1.7;
  resize: vertical;
}

.field input::placeholder,
.field textarea::placeholder {
  color: var(--text-disabled);
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  border-color: var(--brand-primary-hover);
  box-shadow: 0 0 0 3px var(--brand-tint);
}

.field__control input {
  padding-right: 72px;
}

.field__counter {
  position: absolute;
  top: 50%;
  right: var(--sp-3);
  font-size: var(--fs-xs);
  color: var(--text-disabled);
  transform: translateY(-50%);
  pointer-events: none;
}

.field__control--area textarea {
  padding-bottom: var(--sp-6);
}

.field__counter--area {
  top: auto;
  bottom: var(--sp-2);
  transform: none;
}

.field__error {
  margin: 0;
  font-size: var(--fs-xs);
  color: var(--danger-text);
}

.field__hint {
  margin: 0;
  font-size: var(--fs-xs);
  line-height: 1.6;
  color: var(--text-secondary);
}

.article-input {
  min-height: 132px;
}

/* ===== 单选 ===== */
.radio-group {
  display: flex;
  align-items: center;
  gap: var(--sp-6);
  min-height: 42px;
}

.radio {
  display: inline-flex;
  align-items: center;
  gap: var(--sp-2);
  font-size: var(--fs-sm);
  color: var(--text-secondary);
  cursor: pointer;
}

.radio input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.radio__dot {
  position: relative;
  width: 16px;
  height: 16px;
  border: 1.5px solid var(--text-disabled);
  border-radius: 50%;
  transition:
    border-color var(--t-fast),
    background var(--t-fast);
}

.radio--on {
  color: var(--text-primary);
  font-weight: var(--fw-medium);
}

.radio--on .radio__dot {
  border-color: var(--brand-primary-hover);
}

.radio--on .radio__dot::after {
  content: '';
  position: absolute;
  inset: 3px;
  background: var(--brand-primary-hover);
  border-radius: 50%;
}

.radio--disabled {
  color: var(--text-disabled);
  cursor: not-allowed;
}

/* ===== 上传 ===== */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

.dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--sp-2);
  padding: var(--sp-6) var(--sp-4);
  background: var(--brand-tint);
  border: 1.5px dashed rgb(27 67 50 / 0.2);
  border-radius: var(--r-lg);
}

.dropzone__icon {
  width: 30px;
  height: 30px;
  color: var(--brand-primary-hover);
}

.dropzone__btn {
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-semibold);
  color: var(--brand-primary-hover);
  background: none;
  border: none;
  cursor: pointer;
}

.dropzone__btn:hover {
  text-decoration: underline;
}

.dropzone__btn:disabled {
  color: var(--text-disabled);
  cursor: not-allowed;
}

.dropzone__hint {
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.file-chip {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  padding: var(--sp-3) var(--sp-4);
  background: var(--brand-tint);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
}

.file-chip__icon {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  color: var(--brand-primary-hover);
}

.file-chip__name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  font-size: var(--fs-sm);
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ===== 封面 ===== */
.cover-row {
  display: flex;
  align-items: flex-start;
  gap: var(--sp-4);
}

.cover-thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 128px;
  height: 80px;
  color: var(--brand-primary);
  background: var(--brand-tint);
  border-radius: var(--r-md);
  overflow: hidden;
}

.cover-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cover-thumb svg {
  width: 26px;
  height: 26px;
}

.cover-actions {
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
  padding-top: var(--sp-1);
}

.cover-actions__row {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
}

/* ===== 排序号 ===== */
.stepper {
  display: flex;
  align-items: center;
  height: 42px;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  overflow: hidden;
}

.stepper button {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 42px;
  height: 100%;
  font-size: var(--fs-md);
  color: var(--text-secondary);
  background: var(--bg-muted);
  border: none;
  cursor: pointer;
  transition:
    background var(--t-fast),
    color var(--t-fast);
}

.stepper button:hover {
  color: var(--text-primary);
  background: var(--brand-tint);
}

.stepper input {
  flex: 1;
  width: 100%;
  height: 100%;
  padding: 0;
  text-align: center;
  border: none;
  border-radius: 0;
}

.stepper input:focus {
  box-shadow: none;
}

.stepper input::-webkit-outer-spin-button,
.stepper input::-webkit-inner-spin-button {
  margin: 0;
  appearance: none;
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

.btn--sm {
  height: 34px;
  padding: 0 var(--sp-3);
}

.btn__icon {
  width: 15px;
  height: 15px;
}

.btn--secondary {
  color: var(--text-primary);
  background: var(--bg-surface-solid);
  border-color: var(--border);
  box-shadow: var(--shadow-sm);
}

.btn--secondary:hover:not(:disabled) {
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

.btn--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px var(--brand-glow);
}

.btn:disabled {
  box-shadow: none;
  cursor: not-allowed;
  opacity: 0.6;
}

.link-btn {
  padding: 0;
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  color: var(--brand-primary-hover);
  background: none;
  border: none;
  cursor: pointer;
}

.link-btn:hover {
  text-decoration: underline;
}

.link-btn--muted {
  color: var(--text-secondary);
}

.form-error {
  margin: 0;
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-sm);
}

/* ===== 底部 ===== */
.video-form__foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-3);
  position: sticky;
  bottom: 0;
  margin: 0 calc(-1 * var(--sp-6));
  padding: var(--sp-4) var(--sp-6);
  background: var(--bg-surface-solid);
  border-top: 1px solid var(--border);
}

/* ===== 窄屏 ===== */
@media (max-width: 720px) {
  .video-form {
    padding: var(--sp-3);
  }

  .video-form__head,
  .video-form__body {
    padding-left: var(--sp-4);
    padding-right: var(--sp-4);
  }

  .video-form__foot {
    margin: 0 calc(-1 * var(--sp-4));
    padding-left: var(--sp-4);
    padding-right: var(--sp-4);
  }

  .form-row {
    flex-wrap: wrap;
  }

  .field--grow,
  .field--category,
  .field--order {
    flex: 1 1 100%;
  }

  .cover-row {
    flex-direction: column;
  }
}
</style>
