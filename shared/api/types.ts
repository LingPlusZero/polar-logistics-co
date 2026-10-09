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

export type ElfStatus = '正常' | '請假' | '可能失蹤'

// 精靈名冊的一列；年資由 hiredAt 計算，不由後端回傳
export interface Elf {
  id: number
  number: string
  name: string
  departmentId: number
  department: string
  rank: ElfRank
  // YYYY-MM-DD
  hiredAt: string
  status: ElfStatus
  note: string | null
  // 最後一次出勤的日期 YYYY-MM-DD，由出勤紀錄算出；還沒有出勤紀錄為 null
  lastAttendedAt: string | null
}

// 「請假」由請假單決定（有申請且正值假期），不能手動設定
export type EditableElfStatus = Exclude<ElfStatus, '請假'>

// 精靈編號由後端自動產生，新增時不送；編輯時到職日不可改，後端會忽略
export type ElfCreateInput = Omit<Elf, 'id' | 'department' | 'number' | 'status' | 'lastAttendedAt'> & { status: EditableElfStatus }
// 請假中的精靈不送 status（狀態由請假單決定）
export type ElfUpdateInput = Omit<ElfCreateInput, 'hiredAt' | 'status'> & {
  status?: EditableElfStatus
}

export type ElfSortKey = 'number' | 'department' | 'seniority'
export type SortOrder = 'asc' | 'desc'

// 搜尋、篩選、排序、分頁都由後端處理（Demo 模式由前端模擬）
export interface ElfListQuery {
  // 精靈編號或姓名
  search: string
  departmentId: number | null
  sort: ElfSortKey
  order: SortOrder
  page: number
  perPage: number
}

export interface Page<T> {
  items: T[]
  total: number
  page: number
  perPage: number
  lastPage: number
}

// 選單與路由依權限鍵判斷是否顯示，權限由後端依部門與職級給定
export type Permission =
  | 'leave.apply'
  | 'leave.review'
  | 'complaint.file'
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

export type ComplaintStatus = '處理中' | '已結案'

// 人力資源部看的申訴紀錄；elfNumber 是被申訴人，申訴人只存不回傳
export interface Complaint {
  id: number
  elfNumber: string
  elfName: string
  // YYYY-MM-DD
  filedAt: string
  reason: string
  status: ComplaintStatus
  // 結案時的後續處理說明
  resolution: string | null
  // 處理人姓名，結案時由系統帶入
  handler: string | null
}

// 我要申訴：申訴日期取當天、狀態預設處理中，申訴人是登入者，所以只送這兩個
export interface ComplaintInput {
  elfNumber: string
  reason: string
}

// 送出後回傳給申訴人的確認資訊，不含整筆紀錄
export interface ComplaintReceipt {
  id: number
  elfNumber: string
  elfName: string
}

export interface ComplaintListQuery {
  // 被申訴人的精靈編號或姓名
  search: string
  // 被申訴人所屬部門
  departmentId: number | null
  // 申訴日期（YYYY-MM-DD，起迄皆可為空）
  dateFrom: string | null
  dateTo: string | null
  status: ComplaintStatus | null
  page: number
  perPage: number
}

// 精靈出勤紀錄（沒有打卡機制，只有列表）
export interface Attendance {
  id: number
  elfNumber: string
  elfName: string
  // 本地時間 YYYY-MM-DD HH:mm
  clockIn: string
  clockOut: string
  // 工作時數（分鐘），由上下班時間計算
  workMinutes: number
}

export type AttendanceSortKey = 'clockIn' | 'clockOut' | 'number' | 'workMinutes'

// 日期以上班日期篩選（YYYY-MM-DD，起迄皆可為空）；搜尋、篩選、排序、分頁都在後端處理（Demo 由前端模擬）
export interface AttendanceListQuery {
  // 精靈編號或姓名
  search: string
  departmentId: number | null
  dateFrom: string | null
  dateTo: string | null
  sort: AttendanceSortKey
  order: SortOrder
  page: number
  perPage: number
}

export type LeaveType = '普通病假' | '魔力枯竭假' | '被人類目擊後心理創傷假'

export type LeaveStatus = '審核中' | '核准' | '駁回'

// 請假單；elfNumber 是申請人
export interface Leave {
  id: number
  elfNumber: string
  elfName: string
  leaveType: LeaveType
  // 假別固定天數
  days: number
  // YYYY-MM-DD，迄日含當天
  startDate: string
  endDate: string
  appliedAt: string
  status: LeaveStatus
  reviewedAt: string | null
  // 審核人姓名
  reviewer: string | null
  rejectReason: string | null
}

// 申請時只送假別與起日，迄日由假別天數算出，申請日為當天
export interface LeaveInput {
  leaveType: LeaveType
  startDate: string
}

// 我的假單與審核清單共用
export interface LeaveListQuery {
  status: LeaveStatus | null
  // 申請日期（YYYY-MM-DD，起迄皆可為空或省略）
  dateFrom?: string | null
  dateTo?: string | null
  page: number
  perPage: number
}

// 精靈請假紀錄（人力資源部）：搜尋與部門針對申請人，日期以申請日期篩選（起迄皆可為空）
export interface LeaveRecordQuery extends LeaveListQuery {
  // 精靈編號或姓名
  search: string
  departmentId: number | null
  dateFrom: string | null
  dateTo: string | null
}
