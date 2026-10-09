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

// 已登入狀態下任何 API 回 401（權杖失效、閒置被登出）時要通知畫面，由各 app 註冊處理方式
type UnauthorizedHandler = (reason?: string) => void

let unauthorizedHandler: UnauthorizedHandler | null = null

export const setUnauthorizedHandler = (handler: UnauthorizedHandler) => {
  unauthorizedHandler = handler
}

export const notifyUnauthorized = (reason?: string) => unauthorizedHandler?.(reason)
