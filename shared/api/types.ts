// 資料型別，欄位待 docs/db-scheme.md 補齊後再調整

export interface Career {
  id: number
  title: string
  department: string
  description: string
  requirements: string
  note: string
}

export type CareerInput = Omit<Career, 'id'>

export interface AnnualStatic {
  year: number
}
