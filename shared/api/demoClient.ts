import type { ApiClient } from './client'
import type { AnnualStatic, Career, Department } from './types'
import annualData from './demo/data/annual.json'
import careerData from './demo/data/career.json'
import departmentData from './demo/data/department.json'

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

export const demoClient: ApiClient = {
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
