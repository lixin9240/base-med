import { createRouter, createWebHistory } from 'vue-router'
import { getAdminToken } from '@/store/admin/auth'

const WHITE_LIST = ['/admin/login', '/admin/forget-password']

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/mobile/home',
    },
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/views/admin/login/Login.vue'),
    },
    {
      path: '/admin/forget-password',
      name: 'admin-forget-password',
      component: () => import('@/views/admin/login/ForgetPassword.vue'),
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      redirect: '/admin/videos',
      children: [
        {
          path: 'records',
          name: 'admin-records',
          component: () => import('@/views/admin/records/Records.vue'),
        },
        {
          path: 'stats',
          name: 'admin-stats',
          component: () => import('@/views/admin/stats/Stats.vue'),
        },
        {
          path: 'videos',
          name: 'admin-videos',
          component: () => import('@/views/admin/videos/VideoList.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/users/UserList.vue'),
        },
      ],
    },
    {
      path: '/mobile',
      component: () => import('@/layouts/MobileLayout.vue'),
      redirect: '/mobile/home',
      children: [
        {
          path: 'home',
          name: 'mobile-home',
          component: () => import('@/views/mobile/home/Home.vue'),
        },
        {
          path: 'quiz',
          name: 'mobile-quiz',
          component: () => import('@/views/mobile/quiz/Quiz.vue'),
        },
        {
          path: 'learn',
          name: 'mobile-learn',
          component: () => import('@/views/mobile/learn/Learn.vue'),
        },
        {
          path: 'consolidate',
          name: 'mobile-consolidate',
          component: () => import('@/views/mobile/consolidate/Consolidate.vue'),
        },
        {
          path: 'report',
          name: 'mobile-report',
          component: () => import('@/views/mobile/report/Report.vue'),
        },
      ],
    },
  ],
})

router.beforeEach((to) => {
  const token = getAdminToken()

  // 管理端公开页：已登录直接跳首页
  if (WHITE_LIST.includes(to.path)) {
    if (token && to.path === '/admin/login') {
      return { path: '/admin/videos' }
    }
    return true
  }

  // 管理端非公开页：无 token 跳登录，带 redirect
  if (to.path.startsWith('/admin')) {
    if (!token) {
      return {
        path: '/admin/login',
        query: to.fullPath !== '/admin/login' ? { redirect: to.fullPath } : {},
      }
    }
  }

  return true
})

export default router
