import { createRouter, createWebHistory } from 'vue-router'

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

export default router
