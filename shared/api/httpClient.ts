import type { ApiClient } from './client'
import { ApiError } from './errors'
import { getToken } from './session'

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
    // 優先使用後端的錯誤訊息（驗證失敗、查無帳號等）
    const payload = await response.json().catch(() => null)
    throw new ApiError(
      response.status,
      payload?.message ?? `API ${method} ${path} 失敗：${response.status}`,
      payload?.errors,
    )
  }

  return response.status === 204 ? (undefined as T) : response.json()
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
  complaint: {
    list: (query) => {
      const params = new URLSearchParams({ page: String(query.page), perPage: String(query.perPage) })

      if (query.status !== null) {
        params.set('status', query.status)
      }

      return request('GET', `/complaint?${params}`)
    },
    close: (id, resolution) => request('POST', `/complaint/${id}/close`, { resolution }),
    create: (input) => request('POST', '/complaint', input),
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
  statics: {
    annual: () => request('GET', '/statics/annual?limit=10'),
  },
}
