<script setup>
import { computed, ref, watch } from 'vue'
import { deleteAdminVideo } from '@/api'
import { removeVideo } from '@/store/video/video'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  video: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'deleted'])

const deleting = ref(false)
const errorMessage = ref('')

// 题目数由列表接口（2.2.1）的 question_count 提供，前后测共用一套
const questionCount = computed(() => Number(props.video?.questionCount) || 0)

const description = computed(() => {
  const title = props.video?.title || '这条视频'
  if (questionCount.value === 0) {
    return `《${title}》会被删除，已产生的答题记录会保留。`
  }
  return `《${title}》及其 ${questionCount.value} 道题目会一并删除，已产生的答题记录会保留。`
})

watch(
  () => props.open,
  (open) => {
    if (!open) return
    deleting.value = false
    errorMessage.value = ''
  },
)

async function confirmDelete() {
  if (!props.video || deleting.value) return

  deleting.value = true
  errorMessage.value = ''
  try {
    // 先走 2.2.6 软删除：已存在答题记录时后端前置校验不通过，本地记录不动
    await deleteAdminVideo({ id: props.video.apiId ?? props.video.id })
    // 后端删除成功后同步清理本地记录；无本地记录时不影响删除结果
    removeVideo(props.video.id)
    emit('deleted', props.video.id)
    emit('close')
  } catch (error) {
    errorMessage.value = error?.message || '删除失败，请重试'
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div v-if="open" class="delete-modal" @click.self="emit('close')">
    <div
      class="delete-modal__dialog"
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="delete-modal-title"
      aria-describedby="delete-modal-desc"
    >
      <h2 id="delete-modal-title" class="delete-modal__title">删除这条视频？</h2>
      <p id="delete-modal-desc" class="delete-modal__desc">{{ description }}</p>

      <p v-if="errorMessage" class="delete-modal__error">{{ errorMessage }}</p>

      <footer class="delete-modal__foot">
        <button type="button" class="btn btn--secondary" @click="emit('close')">取消</button>
        <button type="button" class="btn btn--danger" :disabled="deleting" @click="confirmDelete">
          {{ deleting ? '删除中…' : '删除' }}
        </button>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.delete-modal {
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

.delete-modal__dialog {
  display: flex;
  flex-direction: column;
  gap: var(--sp-4);
  width: 100%;
  max-width: 560px;
  padding: var(--sp-8);
  background: var(--bg-surface-solid);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-lg);
}

.delete-modal__title {
  margin: 0;
  font-size: var(--fs-2xl);
  font-weight: var(--fw-bold);
  color: var(--text-primary);
}

.delete-modal__desc {
  margin: 0;
  font-size: var(--fs-md);
  line-height: 1.8;
  color: var(--text-secondary);
}

.delete-modal__error {
  margin: 0;
  padding: var(--sp-2) var(--sp-3);
  font-size: var(--fs-sm);
  color: var(--danger-text);
  background: var(--danger-bg);
  border-radius: var(--r-sm);
}

.delete-modal__foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--sp-3);
  margin-top: var(--sp-2);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 96px;
  height: 44px;
  padding: 0 var(--sp-5);
  font-size: var(--fs-md);
  font-weight: var(--fw-semibold);
  border: 1px solid transparent;
  border-radius: var(--r-md);
  cursor: pointer;
  transition:
    background var(--t-fast),
    box-shadow var(--t-fast),
    transform var(--t-fast);
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

.btn--danger {
  color: #fff;
  background: var(--danger);
  box-shadow: 0 8px 20px rgb(230 57 70 / 0.24);
}

.btn--danger:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgb(230 57 70 / 0.3);
}

.btn:disabled {
  box-shadow: none;
  cursor: not-allowed;
  opacity: 0.7;
}

@media (max-width: 640px) {
  .delete-modal {
    padding: var(--sp-3);
  }

  .delete-modal__dialog {
    padding: var(--sp-5);
  }

  .delete-modal__title {
    font-size: var(--fs-xl);
  }
}
</style>
