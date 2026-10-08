import type { ElfProfile } from './types'

// 登入狀態存 sessionStorage：關閉分頁就登出，重新整理不用重新登入
const TOKEN_KEY = 'admin:token'
const PROFILE_KEY = 'admin:profile'

// 隱私模式或停用儲存空間時 sessionStorage 可能丟錯，此時視為未登入
const safely = <T>(action: () => T, fallback: T): T => {
  try {
    return action()
  } catch {
    return fallback
  }
}

export const getToken = () => safely(() => sessionStorage.getItem(TOKEN_KEY), null)

export const getStoredProfile = (): ElfProfile | null =>
  safely(() => {
    const raw = sessionStorage.getItem(PROFILE_KEY)
    return raw ? JSON.parse(raw) : null
  }, null)

export const saveSession = (token: string, profile: ElfProfile) =>
  safely(() => {
    sessionStorage.setItem(TOKEN_KEY, token)
    sessionStorage.setItem(PROFILE_KEY, JSON.stringify(profile))
  }, undefined)

export const clearSession = () =>
  safely(() => {
    sessionStorage.removeItem(TOKEN_KEY)
    sessionStorage.removeItem(PROFILE_KEY)
  }, undefined)
