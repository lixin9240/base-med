<script setup>
const videos = [
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
</script>

<template>
  <section class="video-page">
    <!-- 页头 -->
    <header class="video-page__head">
      <div class="video-page__intro">
        <h1 class="video-page__title">视频管理</h1>
        <p class="video-page__desc">
          用户端免登录，点开就能看。这里维护视频、文字要点和前后测题目。
        </p>
      </div>

      <div class="video-page__actions">
        <button type="button" class="btn btn--secondary">
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

        <button type="button" class="btn btn--primary">
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

    <!-- 视频列表 -->
    <div class="video-table">
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
              <svg
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
                <span class="video-cell__speaker">{{ video.speaker }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 题目 -->
        <div class="col col--quiz">
          <span class="badge" :class="`badge--${video.preTone}`">前测 {{ video.pre }}</span>
          <span class="badge" :class="`badge--${video.postTone}`">后测 {{ video.post }}</span>
        </div>

        <!-- 状态 -->
        <div class="col col--status">
          <span class="status" :class="{ 'status--off': !video.published }">
            <span class="status__dot"></span>
            {{ video.published ? '已上架' : '已下架' }}
          </span>
          <span
            class="switch"
            :class="{ 'switch--on': video.published }"
            role="switch"
            :aria-checked="video.published"
            :aria-label="video.published ? '已上架' : '已下架'"
          >
            <span class="switch__knob"></span>
          </span>
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
          <button type="button" class="link-btn">题目配置</button>
          <button type="button" class="link-btn">编辑</button>
          <button type="button" class="link-btn">生成视频链接</button>
          <button type="button" class="link-btn link-btn--danger">删除</button>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.video-page {
  display: flex;
  flex-direction: column;
  gap: var(--sp-5);
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
  background: #dfe6e1;
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
