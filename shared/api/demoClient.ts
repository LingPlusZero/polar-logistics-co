import type { ApiClient } from './client'
import type { AnnualStatic, Career, Department, ElfProfile } from './types'
import annualData from './demo/data/annual.json'
import careerData from './demo/data/career.json'
import departmentData from './demo/data/department.json'
import elfData from './demo/data/elf.json'
import { ApiError } from './errors'
import { getStoredProfile, getToken } from './session'

// Demo 模式：讀 JSON 快照，寫入只存 sessionStorage，重新整理就重置
function createStore<T>(key: string, seed: T[]) {
  const read = (): T[] => {
    const raw = sessionStorage.getItem(key)
    return raw ? JSON.parse(raw) : seed
  }
  const write = (items: T[]) => sessionStorage.setItem(key, JSON.stringify(items))
  return { read, write }
}

const departments = departmentData as Department[]
const careerStore = createStore<Career>('demo:career', careerData as Career[])

// 與後端行為一致：department 名稱依 departmentId 帶出，空值代表不限部門
const departmentName = (departmentId: number | null) =>
  departments.find((department) => department.id === departmentId)?.name ?? null

const elves = elfData as ElfProfile[]

export const demoClient: ApiClient = {
  auth: {
    // Demo 版密碼就是自己的精靈編號，不套用正式的密碼規則（編號不到 12 字元）
    async login(number, password) {
      const elf = elves.find((item) => item.number === number)

      if (!elf) {
        throw new ApiError(401, '帳號或密碼錯誤')
      }

      if (password !== elf.number) {
        throw new ApiError(401, '帳號或密碼錯誤')
      }

      return { token: `demo-${elf.number}`, elf }
    },
    async logout() {},
    async me() {
      const profile = getStoredProfile()

      if (!getToken() || !profile) {
        throw new ApiError(401, '尚未登入或登入已失效')
      }

      return profile
    },
  },
  career: {
    async list() {
      return careerStore.read()
    },
    async create(input) {
      const items = careerStore.read()
      const created = {
        ...input,
        id: Math.max(0, ...items.map((item) => item.id)) + 1,
        department: departmentName(input.departmentId),
      }
      careerStore.write([...items, created])
      return created
    },
    async update(id, input) {
      const updated = { ...input, id, department: departmentName(input.departmentId) }
      careerStore.write(careerStore.read().map((item) => (item.id === id ? updated : item)))
      return updated
    },
    async remove(id) {
      careerStore.write(careerStore.read().filter((item) => item.id !== id))
    },
  },
  department: {
    async list() {
      return departments
    },
  },
  statics: {
    async annual() {
      // 與 API 行為一致：取最新 10 年，由舊到新
      return (annualData as AnnualStatic[]).slice(-10)
    },
  },
}
