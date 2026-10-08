import { createRouter, createWebHashHistory, createWebHistory } from 'vue-router'
import { isDemoMode } from '@shared/api'

export default createRouter({
  history: isDemoMode
    ? createWebHashHistory(import.meta.env.BASE_URL)
    : createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: () => import('../views/HomeView.vue') },
  ],
})
