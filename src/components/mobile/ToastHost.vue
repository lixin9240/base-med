<script setup>
import { useToast } from '@/utils/toast'

const { message } = useToast()
</script>

<template>
  <div v-if="message" class="toast-host">
    <div class="toast">{{ message }}</div>
  </div>
</template>
