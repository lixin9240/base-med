import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { ensureDevAdminProfile } from '@/store/admin/auth'
import '@/styles/global.css'

ensureDevAdminProfile()

const app = createApp(App)

app.use(router)

app.mount('#app')
