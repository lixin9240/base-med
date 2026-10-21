import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), vueDevTools()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    proxy: {
      // 开发环境把 /api 转发到后端内网穿透地址，前端仍请求相对路径
      '/api': {
        target: 'http://ya36d4ae.natappfree.cc',
        changeOrigin: true,
      },
    },
  },
})
