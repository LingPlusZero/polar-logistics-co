import { api, clearSession, getStoredProfile, getToken, saveSession } from '@shared/api'
import type { ElfProfile, Permission } from '@shared/api'
import { computed, ref } from 'vue'

// 模組層級的狀態：整個應用共用同一份登入資料
const profile = ref<ElfProfile | null>(getStoredProfile())

// 每次載入頁面只向後端確認一次權杖仍有效，之後的換頁不重複打 api
let restorePromise: Promise<void> | null = null

const restore = () => {
  restorePromise ??= (async () => {
    if (!getToken()) {
      profile.value = null
      return
    }

    try {
      profile.value = await api.auth.me()
    } catch {
      // 權杖已失效（例如在別處重新登入）
      clearSession()
      profile.value = null
    }
  })()

  return restorePromise
}

const login = async (number: string, password: string) => {
  const session = await api.auth.login(number, password)

  saveSession(session.token, session.elf)
  profile.value = session.elf
  restorePromise = Promise.resolve()
}

const logout = async () => {
  try {
    await api.auth.logout()
  } catch {
    // 後端已無法連線或權杖已失效時，仍要清掉本機登入狀態
  }

  clearSession()
  profile.value = null
  restorePromise = null
}

// 本機登入狀態已被清掉（閒置或權杖失效），同步記憶體中的資料
const markLoggedOut = () => {
  profile.value = null
  restorePromise = null
}

export function useAuth() {
  return {
    markLoggedOut,
    profile,
    isLoggedIn: computed(() => profile.value !== null),
    can: (permission: Permission) => profile.value?.permissions.includes(permission) ?? false,
    restore,
    login,
    logout,
  }
}
