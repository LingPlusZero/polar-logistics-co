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

// 精靈職級，順序即晉升順序（docs/brand.md）
export type ElfRank = '實習精靈' | '正式精靈' | '資深精靈' | '部長' | '副聖誕老人'

// 選單與路由依權限鍵判斷是否顯示，權限由後端依部門與職級給定
export type Permission =
  | 'leave.apply'
  | 'leave.review'
  | 'password.change'
  | 'elf.roster'
  | 'elf.leave'
  | 'elf.complaint'
  | 'elf.attendance'
  | 'reindeer.manage'
  | 'career.manage'

// 登入者自己的資料，不含權杖
export interface ElfProfile {
  id: number
  number: string
  name: string
  departmentId: number
  department: string
  rank: ElfRank
  permissions: Permission[]
}

export interface AuthSession {
  token: string
  elf: ElfProfile
}

export interface AnnualStatic {
  year: number
  giftsDelivered: number
  growthRate: number
  onTimeRate: number
  completeRate: number
  feedbackRate: number
  note: string | null
}
