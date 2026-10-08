import { createRouter, createWebHashHistory, createWebHistory } from 'vue-router'
import { isDemoMode } from '@shared/api'

const SITE_NAME = '極地物流股份有限公司'

const router = createRouter({
  // GitHub Pages 無法處理前端路由的重新整理，Demo 模式改用 hash 避免 404
  history: isDemoMode
    ? createWebHashHistory(import.meta.env.BASE_URL)
    : createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: () => import('../views/HomeView.vue') },
    { path: '/investors', name: 'investors', meta: { title: '投資人關係' }, component: () => import('../views/InvestorsView.vue') },
    { path: '/careers', name: 'careers', meta: { title: '人才招募' }, component: () => import('../views/CareersView.vue') },
    { path: '/contact', name: 'contact', meta: { title: '聯繫我們' }, component: () => import('../views/ContactView.vue') },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

router.afterEach((to) => {
  const title = to.meta.title as string | undefined
  document.title = title ? `${title}｜${SITE_NAME}` : SITE_NAME
})

export default router
