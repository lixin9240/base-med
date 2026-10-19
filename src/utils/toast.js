import { ref } from 'vue'

const message = ref('')
let timer

export function useToast() {
  return { message }
}

export function toast(text, ms = 2200) {
  message.value = text
  clearTimeout(timer)
  timer = setTimeout(() => {
    message.value = ''
  }, ms)
}
