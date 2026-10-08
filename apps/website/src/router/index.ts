import { createRouter, createWebHashHistory, createWebHistory } from 'vue-router'
import { isDemoMode } from '@shared/api'

export default createRouter({
  // GitHub Pages 無法處理前端路由的重新整理，Demo 模式改用 hash 避免 404
  history: isDemoMode
    ? createWebHashHistory(import.meta.env.BASE_URL)
    : createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: () => import('../views/HomeView.vue') },
    { path: '/investors', name: 'investors', component: () => import('../views/InvestorsView.vue') },
    { path: '/careers', name: 'careers', component: () => import('../views/CareersView.vue') },
    { path: '/contact', name: 'contact', component: () => import('../views/ContactView.vue') },
  ],
})
