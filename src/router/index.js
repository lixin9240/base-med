import { createRouter, createWebHistory } from 'vue-router'
import { getAdminToken, isSkipAdminAuth } from '@/store/admin/auth'

const WHITE_LIST = ['/admin/login', '/admin/forget-password']

/** 分享链接若落在首页或站点根路径，仍进对应用户端答题页 */
function redirectShareEntry(to) {
  const videoId = to.query.videoId || to.query.video_id
  if (!videoId && !to.query.share) return '/mobile/home'
  return {
    path: '/mobile/quiz',
    query: videoId ? { ...to.query, videoId } : { ...to.query },
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // 默认入口：打开站点后进入用户端首页
    {
      path: '/',
      redirect: redirectShareEntry,
    },

    // 管理端 · 登录
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/views/admin/login/Login.vue'),
    },
    // 管理端 · 找回密码
    {
      path: '/admin/forget-password',
      name: 'admin-forget-password',
      component: () => import('@/views/admin/login/ForgetPassword.vue'),
    },
    // 管理端 · 后台布局（默认进入视频管理）
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      redirect: '/admin/videos',
      children: [
        // 管理端 · 答题记录
        {
          path: 'records',
          name: 'admin-records',
          component: () => import('@/views/admin/records/Records.vue'),
        },
        // 管理端 · 数据统计
        {
          path: 'stats',
          name: 'admin-stats',
          component: () => import('@/views/admin/stats/Stats.vue'),
        },
        // 管理端 · 视频管理
        {
          path: 'videos',
          name: 'admin-videos',
          component: () => import('@/views/admin/videos/VideoList.vue'),
        },
        // 管理端 · 用户管理
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/users/UserList.vue'),
        },
      ],
    },

    // 用户端 · 页面布局（默认进入首页）
    {
      path: '/mobile',
      component: () => import('@/layouts/MobileLayout.vue'),
      redirect: redirectShareEntry,
      children: [
        // 用户端 · 首页（视频列表）
        {
          path: 'home',
          name: 'mobile-home',
          component: () => import('@/views/mobile/home/Home.vue'),
          beforeEnter: (to) => {
            const videoId = to.query.videoId || to.query.video_id
            if (!videoId && !to.query.share) return true
            return {
              path: '/mobile/quiz',
              query: videoId ? { ...to.query, videoId } : { ...to.query },
            }
          },
        },
        // 用户端 · 答题（学前 / 看完 / 再次答题）
        {
          path: 'quiz',
          name: 'mobile-quiz',
          component: () => import('@/views/mobile/quiz/Quiz.vue'),
        },
        // 用户端 · 观看视频
        {
          path: 'learn',
          name: 'mobile-learn',
          component: () => import('@/views/mobile/learn/Learn.vue'),
        },
        // 用户端 · 巩固答题
        {
          path: 'consolidate',
          name: 'mobile-consolidate',
          component: () => import('@/views/mobile/consolidate/Consolidate.vue'),
        },
        // 用户端 · 答题结果
        {
          path: 'report',
          name: 'mobile-report',
          component: () => import('@/views/mobile/report/Report.vue'),
        },
        // 用户端 · 幸运抽奖
        {
          path: 'lottery',
          name: 'mobile-lottery',
          component: () => import('@/views/mobile/lottery/Lottery.vue'),
        },
      ],
    },
  ],
})

router.beforeEach((to) => {
  const signedIn = Boolean(getAdminToken()) || isSkipAdminAuth()

  if (WHITE_LIST.includes(to.path)) {
    if (signedIn && to.path === '/admin/login') {
      return { path: '/admin/videos' }
    }
    return true
  }

  if (to.path.startsWith('/admin')) {
    if (!signedIn) {
      return {
        path: '/admin/login',
        query: to.fullPath !== '/admin/login' ? { redirect: to.fullPath } : {},
      }
    }
  }

  return true
})

export default router
