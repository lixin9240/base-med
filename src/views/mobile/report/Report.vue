<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/mobile/AppIcon.vue'
import PageNav from '@/components/mobile/PageNav.vue'
import { QUESTION_TYPE_LABEL, optionLetter } from '@/utils/question'
import { PASS_PERCENT, getRecord } from '@/store'

const route = useRoute()
const router = useRouter()
const record = ref(null)

onMounted(() => {
  record.value = getRecord(String(route.query.recordId || ''))
})

const postDetails = computed(() => {
  if (!record.value) return []
  const all = record.value.detail || []
  const total = record.value.postTotal || 0
  return total ? all.slice(all.length - total) : all
})

function isRight(item, optIndex) {
  const letter = optionLetter(optIndex)
  if (item.type === 'multiple') return Array.isArray(item.answer) && item.answer.includes(letter)
  return item.answer === letter
}

function isPicked(item, optIndex) {
  const letter = optionLetter(optIndex)
  if (item.type === 'multiple') return Array.isArray(item.userAnswer) && item.userAnswer.includes(letter)
  return item.userAnswer === letter
}
</script>

<template>
  <div class="result">
    <PageNav title="答题结果" />

    <template v-if="record">
      <section class="hero" :class="record.passed ? 'hero--pass' : 'hero--fail'">
        <div class="hero__icon">
          <AppIcon :name="record.passed ? 'checkCircle' : 'refresh'" :size="30" />
        </div>
        <h1 class="hero__title">{{ record.passed ? '答得不错！' : '再复习一下？' }}</h1>
        <p class="hero__sub">
          《{{ record.courseTitle }}》·
          {{ record.passed ? `达到 ${PASS_PERCENT}% 通过线` : `答对 ${PASS_PERCENT}% 以上才算通过` }}
        </p>
        <div class="hero__score">
          <span class="hero__num">{{ record.postCorrect }}</span>
          <span class="hero__total">/ {{ record.postTotal }} 题</span>
        </div>
        <p class="hero__percent">正确率 {{ record.percent }}%</p>
        <p v-if="record.preTotal" class="hero__compare">
          学前答题 {{ record.preCorrect }}/{{ record.preTotal }}
          <AppIcon name="arrowRight" :size="13" />
          看完答题 {{ record.postCorrect }}/{{ record.postTotal }}
        </p>
      </section>

      <section class="review">
        <h2 class="review__head">逐题解析</h2>
        <article
          v-for="(item, index) in postDetails"
          :key="item.questionId || index"
          class="rv"
          :class="item.correct ? 'rv--ok' : 'rv--bad'"
        >
          <div class="rv__head">
            <span class="rv__no">{{ index + 1 }}</span>
            <span class="badge" :class="item.correct ? 'badge--success' : 'badge--danger'">
              <span class="badge__dot" />
              {{ item.correct ? '答对' : '答错' }}
            </span>
            <span class="rv__type">{{ QUESTION_TYPE_LABEL[item.type] }}</span>
          </div>
          <p class="rv__stem">{{ item.stem }}</p>
          <ul class="rv__opts">
            <li
              v-for="(opt, i) in item.options"
              :key="i"
              class="rop"
              :class="{ 'is-right': isRight(item, i), 'is-wrong': isPicked(item, i) && !isRight(item, i) }"
            >
              <span class="rop__letter">{{ optionLetter(i) }}</span>
              <span class="rop__text">{{ opt }}</span>
              <span v-if="isRight(item, i)" class="rop__tag">正确答案</span>
              <span v-else-if="isPicked(item, i)" class="rop__tag rop__tag--wrong">您的选择</span>
            </li>
          </ul>
          <p v-if="item.analysis" class="rv__analysis">
            <b>解析：</b>{{ item.analysis }}
          </p>
        </article>
      </section>

      <section class="actions">
        <button
          type="button"
          class="btn btn--primary btn--lg btn--block"
          @click="router.replace({ name: 'mobile-quiz', query: { courseId: record.courseId, stage: 'post' } })"
        >
          再答一次
        </button>
        <div class="actions__row">
          <button
            type="button"
            class="btn btn--secondary btn--md"
            @click="router.replace({ name: 'mobile-learn', query: { courseId: record.courseId } })"
          >
            重看视频
          </button>
          <button type="button" class="btn btn--secondary btn--md" @click="router.replace('/mobile/home')">
            返回列表
          </button>
        </div>
      </section>
    </template>

    <div v-else class="empty">
      <div class="empty__icon">
        <AppIcon name="inbox" :size="44" />
      </div>
      <p class="empty__text">未找到这次答题记录</p>
      <div class="empty__actions">
        <button type="button" class="btn btn--secondary btn--md" @click="router.replace('/mobile/home')">
          返回视频列表
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.result {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg-page);
  padding-bottom: calc(var(--sp-10) + env(safe-area-inset-bottom, 0px));
  margin: calc(-1 * var(--sp-4));
  margin-top: calc(-1 * var(--sp-4));
  margin-left: calc(-1 * max(var(--sp-4), env(safe-area-inset-left, 0px)));
  margin-right: calc(-1 * max(var(--sp-4), env(safe-area-inset-right, 0px)));
}

.hero {
  margin: var(--sp-4);
  padding: var(--sp-6) var(--sp-5);
  border-radius: var(--r-xl);
  text-align: center;
  border: 1px solid var(--border);
  background: var(--bg-surface);
}

.hero--pass {
  background: linear-gradient(180deg, var(--success-bg) 0%, var(--bg-surface) 64%);
  border-color: #bbf7d0;
}

.hero--fail {
  background: linear-gradient(180deg, var(--warning-bg) 0%, var(--bg-surface) 64%);
  border-color: #fde68a;
}

.hero__icon {
  width: 52px;
  height: 52px;
  margin: 0 auto var(--sp-3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero--pass .hero__icon {
  background: var(--success-bg);
  color: var(--success-text);
}

.hero--fail .hero__icon {
  background: var(--warning-bg);
  color: var(--warning-text);
}

.hero__title {
  margin: 0;
  font-size: var(--fs-lg);
  font-weight: var(--fw-bold);
}

.hero__sub {
  margin: var(--sp-1) 0 0;
  font-size: var(--fs-xs);
  color: var(--text-secondary);
  line-height: 1.6;
}

.hero__score {
  margin: var(--sp-4) 0 0;
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: var(--sp-1);
}

.hero__num {
  font-size: 46px;
  font-weight: var(--fw-bold);
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

@media (max-width: 340px) {
  .hero {
    margin: var(--sp-3);
    padding: var(--sp-5) var(--sp-3);
  }
  .hero__num {
    font-size: 36px;
  }
  .review {
    margin: 0 var(--sp-3);
  }
  .actions {
    margin: var(--sp-5) var(--sp-3) 0;
  }
}

.hero__total {
  font-size: var(--fs-md);
  color: var(--text-secondary);
}

.hero__percent {
  margin: var(--sp-1) 0 0;
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.hero__compare {
  display: inline-flex;
  align-items: center;
  gap: var(--sp-2);
  margin: var(--sp-4) 0 0;
  padding: 5px 12px;
  background: var(--bg-muted);
  border-radius: var(--r-full);
  font-size: var(--fs-xs);
  color: var(--text-secondary);
}

.review {
  margin: 0 var(--sp-4);
}

.review__head {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
}

.rv {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: var(--sp-4);
  margin-bottom: var(--sp-3);
}

.rv__head {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  margin-bottom: var(--sp-3);
}

.rv__no {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--bg-muted);
  color: var(--text-secondary);
  font-size: var(--fs-xs);
  font-weight: var(--fw-semibold);
  display: flex;
  align-items: center;
  justify-content: center;
}

.rv__type {
  margin-left: auto;
  font-size: var(--fs-xs);
  color: var(--text-disabled);
}

.rv__stem {
  margin: 0 0 var(--sp-3);
  font-size: var(--fs-sm);
  font-weight: var(--fw-medium);
  line-height: 1.65;
}

.rv__opts {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--sp-2);
}

.rop {
  display: flex;
  align-items: center;
  gap: var(--sp-2);
  padding: var(--sp-2) var(--sp-3);
  border-radius: var(--r-md);
  background: var(--bg-page);
  font-size: var(--fs-sm);
  color: var(--text-secondary);
}

.rop.is-right {
  background: var(--success-bg);
  color: var(--success-text);
}

.rop.is-wrong {
  background: var(--danger-bg);
  color: var(--danger-text);
}

.rop__letter {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--bg-surface);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: var(--fw-semibold);
  flex-shrink: 0;
}

.rop__text {
  flex: 1;
}

.rop__tag {
  font-size: 11px;
  font-weight: var(--fw-semibold);
}

.actions {
  margin: var(--sp-5) var(--sp-4) 0;
}

.actions__row {
  display: flex;
  gap: var(--sp-2);
  margin-top: var(--sp-3);
}

.actions__row > .btn {
  flex: 1;
}
</style>
