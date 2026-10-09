import type {
  AnnualStatic,
  Attendance,
  AttendanceListQuery,
  AuthSession,
  Career,
  CareerInput,
  Complaint,
  ComplaintInput,
  ComplaintListQuery,
  ComplaintReceipt,
  Department,
  Elf,
  ElfCreateInput,
  ElfListQuery,
  ElfProfile,
  ElfUpdateInput,
  Leave,
  LeaveInput,
  LeaveListQuery,
  LeaveRecordQuery,
  Page,
  PersonOption,
  Reindeer,
  ReindeerCreateInput,
  ReindeerListQuery,
  ReindeerUpdateInput,
} from './types'

// 元件只認識這個介面，不知道資料來自 Laravel API 還是 JSON 快照
export interface ApiClient {
  auth: {
    login(number: string, password: string): Promise<AuthSession>
    logout(): Promise<void>
    me(): Promise<ElfProfile>
    changePassword(oldPassword: string, newPassword: string, newPasswordConfirmation: string): Promise<void>
  }
  career: {
    list(): Promise<Career[]>
    create(input: CareerInput): Promise<Career>
    update(id: number, input: CareerInput): Promise<Career>
    remove(id: number): Promise<void>
  }
  attendance: {
    list(query: AttendanceListQuery): Promise<Page<Attendance>>
  }
  complaint: {
    // 人力資源部：申訴紀錄
    list(query: ComplaintListQuery): Promise<Page<Complaint>>
    // 結案：後續處理說明必填，處理人由系統帶入；已結案不能再改
    close(id: number, resolution: string): Promise<Complaint>
    // 我要申訴（所有人）
    create(input: ComplaintInput): Promise<ComplaintReceipt>
  }
  leave: {
    // 自己的假單
    mine(query: LeaveListQuery): Promise<Page<Leave>>
    // 審核範圍內的假單：部長審自己部門（含實習生）、副聖誕老人審各部長
    review(query: LeaveListQuery): Promise<Page<Leave>>
    // 人力資源部：全部精靈的請假紀錄
    records(query: LeaveRecordQuery): Promise<Page<Leave>>
    apply(input: LeaveInput): Promise<Leave>
    approve(id: number): Promise<Leave>
    // 駁回理由必填
    reject(id: number, reason: string): Promise<Leave>
  }
  department: {
    list(): Promise<Department[]>
  }
  elf: {
    list(query: ElfListQuery): Promise<Page<Elf>>
    create(input: ElfCreateInput): Promise<Elf>
    update(id: number, input: ElfUpdateInput): Promise<Elf>
    remove(id: number): Promise<void>
  }
  reindeer: {
    list(query: ReindeerListQuery): Promise<Reindeer[]>
    create(input: ReindeerCreateInput): Promise<Reindeer>
    update(id: number, input: ReindeerUpdateInput): Promise<Reindeer>
    remove(id: number): Promise<void>
    // 新增／修改時可選的照護專員（馴鹿管理部的精靈）
    caretakers(): Promise<PersonOption[]>
    // 登入者擔任照護專員的馴鹿（請假申請可選擇替誰請假）
    mine(): Promise<PersonOption[]>
    // 該馴鹿的請假紀錄（申請日新到舊）
    leaves(id: number): Promise<Leave[]>
  }
  statics: {
    annual(): Promise<AnnualStatic[]>
  }
}
