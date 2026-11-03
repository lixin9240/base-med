import { createRouter, createWebHistory } from 'vue-router'
// 路由守卫已临时关闭，恢复时一并取消下面两行注释。见 docs/代码修改记录/2026-11-03.md
// import { getAdminToken, isSkipAdminAuth } from '@/store/admin/auth'
// const WHITE_LIST = ['/admin/login', '/admin/forget-password']

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

// 临时关闭管理端登录拦截，保证未登录也能走完页面。
// 恢复时取消注释，并恢复文件顶部的 getAdminToken / WHITE_LIST。
// router.beforeEach((to) => {
//   const signedIn = Boolean(getAdminToken()) || isSkipAdminAuth()
//
//   if (WHITE_LIST.includes(to.path)) {
//     if (signedIn && to.path === '/admin/login') {
//       return { path: '/admin/videos' }
//     }
//     return true
//   }
//
//   if (to.path.startsWith('/admin')) {
//     if (!signedIn) {
//       return {
//         path: '/admin/login',
//         query: to.fullPath !== '/admin/login' ? { redirect: to.fullPath } : {},
//       }
//     }
//   }
//
//   return true
// })

export default router
