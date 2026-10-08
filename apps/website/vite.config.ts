import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'node:url'
import { defineConfig, loadEnv } from 'vite'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    // GitHub Pages 以子路徑區分各前端，由 .env.demo 指定
    base: env.VITE_BASE || '/',
    plugins: [vue()],
    resolve: {
      alias: {
        '@shared': fileURLToPath(new URL('../../shared', import.meta.url)),
      },
      // shared/ 內的檔案也要使用本專案安裝的 vue
      dedupe: ['vue'],
    },
    server: {
      host: true,
      // shared/ 在專案目錄之外，需開放讀取
      fs: { allow: ['../..'] },
      // 讓前端連得上 api/，docker 內指向 nginx 容器
      proxy: {
        '/api': {
          target: env.API_PROXY_TARGET || 'http://localhost:8080',
          changeOrigin: true,
        },
      },
      // Windows 上 bind mount 收不到檔案事件，需改用輪詢
      watch: { usePolling: true },
    },
  }
})
