import type { AnnualStatic, AuthSession, Career, CareerInput, Department, ElfProfile } from './types'

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
  department: {
    list(): Promise<Department[]>
  }
  statics: {
    annual(): Promise<AnnualStatic[]>
  }
}
