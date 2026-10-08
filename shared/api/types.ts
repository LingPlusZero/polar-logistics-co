// 與 docs/api.md 的回傳欄位一致（camelCase）

export interface Department {
  id: number
  name: string
  duty: string | null
}

export interface Career {
  id: number
  title: string
  departmentId: number | null
  // 部門名稱；空值代表不限部門
  department: string | null
  description: string
  requirements: string
  benefits: string | null
  promotion: string | null
  note: string | null
}

// 寫入時只送 departmentId，department 名稱由後端依關聯帶出
export type CareerInput = Omit<Career, 'id' | 'department'>

export interface AnnualStatic {
  year: number
  giftsDelivered: number
  growthRate: number
  onTimeRate: number
  completeRate: number
  feedbackRate: number
  note: string | null
}
