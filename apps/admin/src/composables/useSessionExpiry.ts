import { api, clearSession, getToken } from '@shared/api'
import router from '../router'
import { useAuth } from './useAuth'

export type ExpiryNotice = 'idle-timeout' | 'session-expired'

// 被動登出（閒置、權杖失效）：清掉本機登入狀態，回登入頁並帶上原因，登入頁會顯示提示。
// 與使用者自己按登出不同，會記住目前頁面，重新登入後回來
export async function expireSession(notice: ExpiryNotice, notifyServer = false) {
  const { profile, markLoggedOut } = useAuth()

  // 已經登出（例如同時有多個請求都回 401）就不重複處理
  if (!getToken() && profile.value === null) {
    return
  }

  // 前端計時到了、但後端還沒失效時，主動讓權杖作廢；失敗也無妨
  if (notifyServer) {
    await api.auth.logout().catch(() => undefined)
  }

  clearSession()
  markLoggedOut()

  const current = router.currentRoute.value

  if (current.name !== 'login') {
    await router.replace({
      name: 'login',
      query: { notice, ...(current.path === '/' ? {} : { redirect: current.fullPath }) },
    })
  }
}
