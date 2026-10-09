import { api } from '@shared/api'
import { onBeforeUnmount, onMounted } from 'vue'
import { expireSession } from './useSessionExpiry'

// 與後端 Elf::IDLE_TIMEOUT_MINUTES 同步
export const IDLE_TIMEOUT_MS = 30 * 60 * 1000

// 使用者有操作、但沒有打 API 時，後端看不到活動；定期送一次請求讓權杖維持有效
const KEEP_ALIVE_MS = 5 * 60 * 1000
// 計時器在背景分頁會被瀏覽器延後，所以每隔一段時間用時間戳記比對，而不是設一個 30 分鐘的 setTimeout
const CHECK_INTERVAL_MS = 15 * 1000

const ACTIVITY_EVENTS = ['pointerdown', 'keydown', 'wheel', 'touchstart'] as const

// 登入後的頁面使用：閒置 30 分鐘就自動登出並回登入頁提示
export function useIdleLogout() {
  let lastActivity = Date.now()
  let lastKeepAlive = Date.now()
  let timer: ReturnType<typeof setInterval> | undefined

  const onActivity = () => {
    lastActivity = Date.now()
  }

  const check = () => {
    const now = Date.now()

    if (now - lastActivity >= IDLE_TIMEOUT_MS) {
      expireSession('idle-timeout', true)
      return
    }

    // 最近有操作才續命；沒操作就讓後端自己判定閒置
    if (lastActivity > lastKeepAlive && now - lastKeepAlive >= KEEP_ALIVE_MS) {
      lastKeepAlive = now
      // 若權杖已失效，httpClient 會通知 401 並自動登出
      api.auth.me().catch(() => undefined)
    }
  }

  // 從休眠或背景分頁切回來時立刻檢查，不用等下一輪
  const onVisible = () => {
    if (document.visibilityState === 'visible') {
      check()
    }
  }

  onMounted(() => {
    ACTIVITY_EVENTS.forEach((name) => window.addEventListener(name, onActivity, { passive: true }))
    document.addEventListener('visibilitychange', onVisible)
    timer = setInterval(check, CHECK_INTERVAL_MS)
  })

  onBeforeUnmount(() => {
    ACTIVITY_EVENTS.forEach((name) => window.removeEventListener(name, onActivity))
    document.removeEventListener('visibilitychange', onVisible)
    clearInterval(timer)
  })
}
