import type {
  AnnualStatic,
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
  Page,
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
  complaint: {
    // 人力資源部：申訴紀錄
    list(query: ComplaintListQuery): Promise<Page<Complaint>>
    // 結案：後續處理說明必填，處理人由系統帶入；已結案不能再改
    close(id: number, resolution: string): Promise<Complaint>
    // 我要申訴（所有人）
    create(input: ComplaintInput): Promise<ComplaintReceipt>
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
  statics: {
    annual(): Promise<AnnualStatic[]>
  }
}
