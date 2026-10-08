import { createApp } from 'vue'
import '@shared/styles/base.css'
import App from './App.vue'
import router from './router'

createApp(App).use(router).mount('#app')
