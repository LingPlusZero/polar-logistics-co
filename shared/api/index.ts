import type { ApiClient } from './client'
import { demoClient } from './demoClient'
import { httpClient } from './httpClient'

export type { ApiClient } from './client'
export { ApiError } from './errors'
export { PASSWORD_MIN_LENGTH, PASSWORD_RULES, isValidPassword } from './password'
export {
  clearSession,
  getStoredProfile,
  getToken,
  saveSession,
  setUnauthorizedHandler,
} from './session'
export { LEAVE_TYPES, PEAK_SEASON_MESSAGE, leaveDays, leaveEndDate, touchesPeakSeason } from './leave'
export { MAINTENANCE_INTERVAL_MONTHS, nextMaintenanceDate } from './reindeer'
export * from './types'

export const isDemoMode = import.meta.env.VITE_DEMO_MODE === 'true'

// 依環境變數切換實作，元件統一從這裡取得 api
export const api: ApiClient = isDemoMode ? demoClient : httpClient
