import type { ApiClient } from './client'
import type { AnnualStatic, Career } from './types'
import annualData from './demo/data/annual.json'
import careerData from './demo/data/career.json'

// Demo 模式：讀 JSON 快照，寫入只存 sessionStorage，重新整理就重置
function createStore<T>(key: string, seed: T[]) {
  const read = (): T[] => {
    const raw = sessionStorage.getItem(key)
    return raw ? JSON.parse(raw) : seed
  }
  const write = (items: T[]) => sessionStorage.setItem(key, JSON.stringify(items))
  return { read, write }
}

const careerStore = createStore<Career>('demo:career', careerData as Career[])

export const demoClient: ApiClient = {
  career: {
    async list() {
      return careerStore.read()
    },
    async create(input) {
      const items = careerStore.read()
      const created = { ...input, id: Math.max(0, ...items.map((item) => item.id)) + 1 }
      careerStore.write([...items, created])
      return created
    },
    async update(id, input) {
      const updated = { ...input, id }
      careerStore.write(careerStore.read().map((item) => (item.id === id ? updated : item)))
      return updated
    },
    async remove(id) {
      careerStore.write(careerStore.read().filter((item) => item.id !== id))
    },
  },
  statics: {
    async annual() {
      return annualData as AnnualStatic[]
    },
  },
}
