import type { ApiClient } from './client'

// 正常模式：打 Laravel API，開發時由 vite proxy 轉發 /api
async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const response = await fetch(`/api${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  })

  if (!response.ok) {
    throw new Error(`API ${method} ${path} 失敗：${response.status}`)
  }

  return response.status === 204 ? (undefined as T) : response.json()
}

export const httpClient: ApiClient = {
  career: {
    list: () => request('GET', '/career'),
    create: (input) => request('POST', '/career', input),
    update: (id, input) => request('PUT', `/career/${id}`, input),
    remove: (id) => request('DELETE', `/career/${id}`),
  },
  department: {
    list: () => request('GET', '/department'),
  },
  statics: {
    annual: () => request('GET', '/statics/annual?limit=10'),
  },
}
