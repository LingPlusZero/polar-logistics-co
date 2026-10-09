import type { ApiClient } from './client'
import type { LeaveListQuery } from './types'
import { ApiError } from './errors'
import { getToken, notifyUnauthorized } from './session'

// 正常模式：打 Laravel API，開發時由 vite proxy 轉發 /api
async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const token = getToken()
  const response = await fetch(`/api${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(body ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  })

  if (!response.ok) {
    // 優先使用後端的錯誤訊息（驗證失敗、查無帳號等）；5xx 或沒有訊息時只顯示通用文字，
    // 不把狀態碼、請求路徑等內部細節顯示給使用者
    const payload = await response.json().catch(() => null)

    // 帶著權杖卻被拒絕＝登入已失效（登入端點的 401 是帳密錯誤，不算）
    if (response.status === 401 && token && path !== '/auth/login') {
      notifyUnauthorized(payload?.reason)
    }

    throw new ApiError(
      response.status,
      response.status >= 500 ? '伺服器發生錯誤，請稍後再試' : (payload?.message ?? '操作失敗，請稍後再試'),
      payload?.errors,
      payload?.reason,
    )
  }

  return response.status === 204 ? (undefined as T) : response.json()
}

// 我的假單、審核清單與請假紀錄共用的查詢參數
const leaveParams = (query: LeaveListQuery) => {
  const params = new URLSearchParams({ page: String(query.page), perPage: String(query.perPage) })

  if (query.status !== null) {
    params.set('status', query.status)
  }

  if (query.dateFrom) {
    params.set('dateFrom', query.dateFrom)
  }

  if (query.dateTo) {
    params.set('dateTo', query.dateTo)
  }

  return params
}

export const httpClient: ApiClient = {
  auth: {
    login: (number, password) => request('POST', '/auth/login', { number, password }),
    logout: () => request('POST', '/auth/logout'),
    me: () => request('GET', '/auth/me'),
    changePassword: (oldPassword, newPassword, newPasswordConfirmation) =>
      request('PUT', '/auth/password', { oldPassword, newPassword, newPasswordConfirmation }),
  },
  career: {
    list: () => request('GET', '/career'),
    create: (input) => request('POST', '/career', input),
    update: (id, input) => request('PUT', `/career/${id}`, input),
    remove: (id) => request('DELETE', `/career/${id}`),
  },
  attendance: {
    list: (query) => {
      const params = new URLSearchParams({
        sort: query.sort,
        order: query.order,
        page: String(query.page),
        perPage: String(query.perPage),
      })

      if (query.search.trim() !== '') {
        params.set('search', query.search.trim())
      }

      if (query.departmentId !== null) {
        params.set('departmentId', String(query.departmentId))
      }

      if (query.dateFrom) {
        params.set('dateFrom', query.dateFrom)
      }

      if (query.dateTo) {
        params.set('dateTo', query.dateTo)
      }

      return request('GET', `/attendance?${params}`)
    },
  },
  complaint: {
    list: (query) => {
      const params = new URLSearchParams({ page: String(query.page), perPage: String(query.perPage) })

      if (query.status !== null) {
        params.set('status', query.status)
      }

      if (query.search.trim() !== '') {
        params.set('search', query.search.trim())
      }

      if (query.departmentId !== null) {
        params.set('departmentId', String(query.departmentId))
      }

      if (query.dateFrom) {
        params.set('dateFrom', query.dateFrom)
      }

      if (query.dateTo) {
        params.set('dateTo', query.dateTo)
      }

      return request('GET', `/complaint?${params}`)
    },
    close: (id, resolution) => request('POST', `/complaint/${id}/close`, { resolution }),
    create: (input) => request('POST', '/complaint', input),
  },
  leave: {
    mine: (query) => request('GET', `/leave/mine?${leaveParams(query)}`),
    review: (query) => request('GET', `/leave/review?${leaveParams(query)}`),
    records: (query) => {
      const params = leaveParams(query)

      if (query.search.trim() !== '') {
        params.set('search', query.search.trim())
      }

      if (query.departmentId !== null) {
        params.set('departmentId', String(query.departmentId))
      }

      return request('GET', `/leave/records?${params}`)
    },
    apply: (input) => request('POST', '/leave', input),
    approve: (id) => request('POST', `/leave/${id}/approve`),
    reject: (id, reason) => request('POST', `/leave/${id}/reject`, { reason }),
  },
  department: {
    list: () => request('GET', '/department'),
  },
  elf: {
    list: (query) => {
      const params = new URLSearchParams({
        sort: query.sort,
        order: query.order,
        page: String(query.page),
        perPage: String(query.perPage),
      })

      if (query.search.trim() !== '') {
        params.set('search', query.search.trim())
      }

      if (query.departmentId !== null) {
        params.set('departmentId', String(query.departmentId))
      }

      return request('GET', `/elf?${params}`)
    },
    create: (input) => request('POST', '/elf', input),
    update: (id, input) => request('PUT', `/elf/${id}`, input),
    remove: (id) => request('DELETE', `/elf/${id}`),
  },
  reindeer: {
    list: (query) => request('GET', `/reindeer?sort=${query.sort}&order=${query.order}`),
    create: (input) => request('POST', '/reindeer', input),
    update: (id, input) => request('PUT', `/reindeer/${id}`, input),
    remove: (id) => request('DELETE', `/reindeer/${id}`),
    caretakers: () => request('GET', '/reindeer/caretakers'),
    mine: () => request('GET', '/reindeer/mine'),
    leaves: (id) => request('GET', `/reindeer/${id}/leave`),
  },
  statics: {
    annual: () => request('GET', '/statics/annual?limit=10'),
  },
}
