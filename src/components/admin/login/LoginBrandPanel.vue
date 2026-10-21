<template>
  <aside class="brand-panel">
    <div class="brand-content">
      <!-- 品牌标识 -->
      <div class="brand">
        <div class="brand-logo">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
          >
            <path
              d="M12 5.5v13M5.5 12h13"
              stroke="#fff"
              stroke-width="3.2"
              stroke-linecap="round"
            />
          </svg>
        </div>
        <div class="brand-text">
          <h1 class="brand-name">基层医声.Dr</h1>
          <p class="brand-subtitle">医学科普内容管理</p>
        </div>
      </div>

      <!-- 主标题 -->
      <h2 class="brand-heading">做专业、准确的<br />医学科普</h2>

      <!-- 说明 -->
      <p class="brand-intro">
        管理端只做三件事：上传科普视频、配好前后的题目、看看大家答得怎么样。
      </p>

      <!-- 功能列表 -->
      <ul class="feature-list">
        <li class="feature-item">
          <div class="feature-icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <rect x="2.5" y="6" width="13" height="12" rx="3" />
              <path d="M15.5 10.6l4.6-2.7a.8.8 0 0 1 1.2.7v6.8a.8.8 0 0 1-1.2.7l-4.6-2.7" />
            </svg>
          </div>
          <div class="feature-body">
            <h3 class="feature-title">视频管理</h3>
            <p class="feature-desc">上传视频与文字要点，一键上架或下架</p>
          </div>
        </li>
        <li class="feature-item">
          <div class="feature-icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M2.8 6.4l1.6 1.6 2.8-3.1M2.8 13l1.6 1.6 2.8-3.1M2.8 19.6l1.6 1.6 2.8-3.1" />
              <path d="M11 6h10M11 12.6h10M11 19.2h10" />
            </svg>
          </div>
          <div class="feature-body">
            <h3 class="feature-title">题目配置</h3>
            <p class="feature-desc">每条视频配 3 道题，前后测共用，也可用 Excel 批量导入</p>
          </div>
        </li>
        <li class="feature-item">
          <div class="feature-icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M5 20V11M12 20V4M19 20v-6" />
            </svg>
          </div>
          <div class="feature-body">
            <h3 class="feature-title">答题数据</h3>
            <p class="feature-desc">看答题记录与正确率，哪道题错得多一目了然</p>
          </div>
        </li>
      </ul>
    </div>
    <div class="brand-footer">© 2026 基层医声.Dr. All Rights Reserved.</div>
  </aside>
</template>

<style scoped>
/* ===== 左侧品牌区 ===== */
.brand-panel {
  position: relative;
  flex: 1.1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 72px 80px;
  background: var(--bg-surface-solid);
  border-right: 1px solid var(--border);
  color: var(--text-primary);
  overflow: hidden;
}

.brand-content {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 620px;
}

/* 品牌标识行 */
.brand {
  display: flex;
  align-items: center;
  gap: 18px;
}

.brand-logo {
  flex: none;
  width: 64px;
  height: 64px;
  border-radius: var(--r-lg);
  background: linear-gradient(
    165deg,
    var(--brand-primary-hover) 0%,
    var(--brand-primary) 55%,
    var(--brand-primary-active) 100%
  );
  box-shadow: 0 8px 20px var(--brand-glow);
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-logo svg {
  width: 34px;
  height: 34px;
}

.brand-name {
  font-size: 28px;
  font-weight: var(--fw-bold);
  margin: 0;
  letter-spacing: 0.5px;
  color: var(--text-primary);
}

.brand-subtitle {
  font-size: 17px;
  font-weight: 400;
  margin: 4px 0 0;
  color: var(--text-secondary);
}

/* 主标题 */
.brand-heading {
  margin: 56px 0 0;
  font-size: 52px;
  line-height: 1.28;
  font-weight: 800;
  letter-spacing: 1px;
  color: var(--text-primary);
}

/* 说明文字 */
.brand-intro {
  margin: 28px 0 0;
  font-size: 19px;
  line-height: 1.85;
  color: var(--text-secondary);
}

/* 功能列表 */
.feature-list {
  list-style: none;
  margin: 48px 0 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.feature-icon {
  flex: none;
  width: 58px;
  height: 58px;
  border-radius: var(--r-lg);
  background: var(--brand-tint);
  border: 1px solid var(--border-light);
  color: var(--brand-primary-hover);
  display: flex;
  align-items: center;
  justify-content: center;
}

.feature-icon svg {
  width: 28px;
  height: 28px;
}

.feature-body {
  padding-top: 2px;
}

.feature-title {
  margin: 0 0 6px;
  font-size: 21px;
  font-weight: var(--fw-bold);
  color: var(--text-primary);
}

.feature-desc {
  margin: 0;
  font-size: 17px;
  line-height: 1.5;
  color: var(--text-secondary);
}

.brand-footer {
  position: absolute;
  bottom: 28px;
  left: 80px;
  font-size: 13px;
  color: var(--text-disabled);
}

/* ===== 响应式：窄屏改为上下布局 ===== */
@media (max-width: 768px) {
  .brand-panel {
    flex: none;
    padding: 48px 24px 32px;
    border-right: none;
    border-bottom: 1px solid var(--border);
  }

  .brand-logo {
    width: 52px;
    height: 52px;
    border-radius: 13px;
  }

  .brand-logo svg {
    width: 28px;
    height: 28px;
  }

  .brand-name {
    font-size: 22px;
  }

  .brand-subtitle {
    font-size: 15px;
  }

  .brand-heading {
    margin-top: 36px;
    font-size: 34px;
  }

  .brand-intro {
    margin-top: 20px;
    font-size: 16px;
  }

  .feature-list {
    margin-top: 32px;
    gap: 22px;
  }

  .feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 13px;
  }

  .feature-title {
    font-size: 18px;
  }

  .feature-desc {
    font-size: 15px;
  }

  .brand-footer {
    position: static;
    margin-top: 24px;
  }
}
</style>
