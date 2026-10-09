import { setUnauthorizedHandler } from '@shared/api'
import { createApp } from 'vue'
import '@shared/styles/base.css'
import App from './App.vue'
import { expireSession } from './composables/useSessionExpiry'
import router from './router'
import './styles/controls.css'

// 已登入時任何 API 回 401：後端說是閒置就提示閒置，其他原因提示登入已失效
setUnauthorizedHandler((reason) => {
  expireSession(reason === 'idle' ? 'idle-timeout' : 'session-expired')
})

createApp(App).use(router).mount('#app')
