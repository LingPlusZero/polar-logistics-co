import { isDemoMode } from '@shared/api'
import type { Permission } from '@shared/api'
import { createRouter, createWebHashHistory, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { flattenMenu } from '../config/menu'

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    // 不需登入即可進入
    public?: boolean
    permission?: Permission
  }
}

const TITLE_SUFFIX = '精靈管理系統｜極地物流'

const router = createRouter({
  history: isDemoMode
    ? createWebHashHistory(import.meta.env.BASE_URL)
    : createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { public: true, title: '登入' },
    },
    {
      path: '/',
      component: () => import('../layouts/AdminLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('../views/HomeView.vue'),
          meta: { title: '首頁' },
        },
        {
          path: 'password',
          name: 'password',
          component: () => import('../views/PlaceholderView.vue'),
          meta: { title: '修改密碼', permission: 'password.change' },
        },
        // 選單上的頁面，頁面尚未完成的先顯示「建置中」
        ...flattenMenu().map((item) => ({
          path: item.path.slice(1),
          component: item.component ?? (() => import('../views/PlaceholderView.vue')),
          meta: { title: item.label, permission: item.permission },
        })),
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: { name: 'home' } },
  ],
})

router.beforeEach(async (to) => {
  const { isLoggedIn, can, restore } = useAuth()

  await restore()

  if (to.meta.public) {
    // 已登入就不用再看登入頁
    return to.name === 'login' && isLoggedIn.value ? { name: 'home' } : true
  }

  if (!isLoggedIn.value) {
    return {
      name: 'login',
      // 登入後回到原本要去的頁面
      query: to.path === '/' ? undefined : { redirect: to.fullPath },
    }
  }

  // 沒有權限的頁面一律回首頁，不洩漏頁面是否存在
  if (to.meta.permission && !can(to.meta.permission)) {
    return { name: 'home' }
  }

  return true
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title}｜${TITLE_SUFFIX}` : TITLE_SUFFIX
})

export default router
