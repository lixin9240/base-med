<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import { ensureLearner, getProgress, listPublishedCourses, listQuestions } from '@/store'

const router = useRouter()
const courses = ref([])
const userId = ref('')

onMounted(() => {
  const learner = ensureLearner()
  userId.value = learner.userId
  courses.value = listPublishedCourses()
})

function categoryIcon(category) {
  if (category === '影像检查') return 'monitor'
  if (category === '急救技能') return 'heartPulse'
  if (category === '用药安全') return 'activity'
  return 'stethoscope'
}

function categoryTone(category) {
  if (category === '影像检查') return 'teal'
  if (category === '妇幼保健' || category === '慢病管理') return 'pink'
  if (category === '用药安全') return 'lavender'
  return 'mint'
}

function progressHint(courseId) {
  const p = getProgress(userId.value, courseId)
  if (p.postDone) return p.passed ? `已答对 ${p.bestPercent}%` : '已作答'
  if (p.preDone) return '前测已完成，待看视频'
  return ''
}

function openCourse(course) {
  const pre = listQuestions(course.id, 'pre')
  if (pre.length) {
    router.push({ name: 'mobile-quiz', query: { courseId: course.id, stage: 'pre' } })
  } else {
    router.push({ name: 'mobile-learn', query: { courseId: course.id } })
  }
}
</script>

<template>
  <div class="home">
    <header class="home__bar">
      <span class="home__logo">
        <AppIcon name="cross" :size="16" />
      </span>
      <span class="home__bar-title">基层医声.Dr</span>
    </header>

    <section class="home__hero">
      <h1>医学常识，讲给您听</h1>
      <p>选一条视频，看完答几道题，看看有没有记住。</p>
    </section>

    <section class="home__list">
      <div v-if="courses.length === 0" class="empty">
        <div class="empty__icon">
          <AppIcon name="inbox" :size="48" />
        </div>
        <p class="empty__text">暂无视频，请稍后再来。</p>
      </div>

      <article
        v-for="item in courses"
        :key="item.id"
        class="vcard"
        tabindex="0"
        role="button"
        @click="openCourse(item)"
        @keyup.enter="openCourse(item)"
      >
        <div class="vcard__thumb">
          <img v-if="item.cover" :src="item.cover" :alt="item.title" />
          <span v-else class="vcard__thumb-ph" :class="`vcard__thumb-ph--${categoryTone(item.category)}`">
            <AppIcon :name="categoryIcon(item.category)" :size="22" />
          </span>
          <span class="vcard__play">
            <AppIcon name="play" :size="12" />
          </span>
        </div>
        <div class="vcard__body">
          <h2 class="vcard__title">{{ item.title }}</h2>
          <p class="vcard__meta">
            <span class="badge badge--info">{{ item.category }}</span>
            <span>{{ item.speaker }}</span>
          </p>
          <p class="vcard__intro">{{ item.intro }}</p>
          <p v-if="progressHint(item.id)" class="vcard__state">
            <AppIcon name="checkCircle" :size="13" />
            {{ progressHint(item.id) }}
          </p>
        </div>
        <span class="vcard__go">
          <AppIcon name="chevronRight" :size="18" />
        </span>
      </article>
    </section>

    <footer class="home__foot">本平台内容仅供健康科普学习，不构成诊疗建议。</footer>
  </div>
</template>

<style scoped>
.home {
  min-height: 100vh;
  background: var(--bg-page);
  padding-bottom: var(--sp-8);
  margin: calc(-1 * var(--sp-4));
}

.home__bar {
  height: 48px;
  background: var(--brand-primary);
  color: #fff;
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  padding: 0 var(--sp-4);
  position: sticky;
  top: 0;
  z-index: 20;
}

.home__logo {
  width: 26px;
  height: 26px;
  border-radius: var(--r-sm);
  background: rgb(255 255 255 / 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.home__bar-title {
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
}

.home__hero {
  padding: var(--sp-5) var(--sp-4) var(--sp-4);
}

.home__hero h1 {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: var(--fw-bold);
}

.home__hero p {
  margin: var(--sp-1) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.home__list {
  padding: 0 var(--sp-4);
  display: flex;
  flex-direction: column;
  gap: var(--sp-3);
}

.vcard {
  display: flex;
  align-items: center;
  gap: var(--sp-3);
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
  padding: var(--sp-3);
  cursor: pointer;
}

.vcard__thumb {
  position: relative;
  width: 104px;
  height: 70px;
  border-radius: var(--r-lg);
  overflow: hidden;
  flex-shrink: 0;
  background: var(--brand-tint);
}

.vcard__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.vcard__thumb-ph {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--brand-primary);
}

.vcard__play {
  position: absolute;
  right: 5px;
  bottom: 5px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgb(17 24 39 / 0.6);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.vcard__body {
  flex: 1;
  min-width: 0;
}

.vcard__title {
  margin: 0;
  font-size: var(--fs-base);
  font-weight: var(--fw-semibold);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.vcard__meta {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  margin: var(--sp-2) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.vcard__intro {
  margin: var(--sp-1) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-disabled);
  line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.vcard__state {
  display: flex;
  align-items: center;
  gap: 4px;
  margin: var(--sp-1) 0 0;
  font-size: 11px;
  color: var(--success-text);
}

.vcard__go {
  color: var(--text-disabled);
  flex-shrink: 0;
}

.home__foot {
  margin-top: var(--sp-6);
  padding: 0 var(--sp-4);
  text-align: center;
  font-size: 11px;
  color: var(--text-disabled);
  line-height: 1.6;
}
</style>
