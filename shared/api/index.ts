import type { ApiClient } from './client'
import { demoClient } from './demoClient'
import { httpClient } from './httpClient'

export type { ApiClient } from './client'
export * from './types'

export const isDemoMode = import.meta.env.VITE_DEMO_MODE === 'true'

// 依環境變數切換實作，元件統一從這裡取得 api
export const api: ApiClient = isDemoMode ? demoClient : httpClient
