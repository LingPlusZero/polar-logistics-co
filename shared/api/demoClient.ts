import type { ApiClient } from './client'
import type { AnnualStatic, Attendance, Career, Complaint, Department, Elf, ElfProfile } from './types'
import annualData from './demo/data/annual.json'
import careerData from './demo/data/career.json'
import complaintData from './demo/data/complaint.json'
import departmentData from './demo/data/department.json'
import elfData from './demo/data/elf.json'
import rosterData from './demo/data/roster.json'
import { generateAttendance } from './demo/attendance'
import { ApiError } from './errors'
import { PASSWORD_MIN_LENGTH, isValidPassword } from './password'
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

// 出勤紀錄依今天往前產生（唯讀），同一個分頁只算一次
let attendanceCache: Attendance[] | null = null
const attendanceRecords = () => (attendanceCache ??= generateAttendance(elves))

// 最後出勤日：該精靈最近一筆上班時間的日期，沒有出勤紀錄為 null（與後端 ElfResource 一致）
const withLastAttended = (elf: StoredElf): Elf => {
  const last = attendanceRecords()
    .filter((record) => record.elfNumber === elf.number)
    .map((record) => record.clockIn)
    .sort()
    .at(-1)

  return { ...elf, lastAttendedAt: last ? last.slice(0, 10) : null }
}
// 名冊存的欄位不含最後出勤日（它由出勤紀錄算出，輸出時才帶上）
type StoredElf = Omit<Elf, 'lastAttendedAt'>
const rosterStore = createStore<StoredElf>('demo:roster', rosterData as StoredElf[])
const complaintStore = createStore<Complaint>('demo:complaint', complaintData as Complaint[])

const PASSWORD_KEY_PREFIX = 'demo:password:'

const currentPassword = (number: string) =>
  sessionStorage.getItem(`${PASSWORD_KEY_PREFIX}${number}`) ?? number

export const demoClient: ApiClient = {
  auth: {
    // Demo 版預設密碼就是自己的精靈編號，不套用正式的密碼規則（編號不到 12 字元）；
    // 改過密碼的話以新密碼為準（存 sessionStorage，與其他 Demo 資料一樣不會送出）
    async login(number, password) {
      const elf = elves.find((item) => item.number === number)

      if (!elf || password !== currentPassword(elf.number)) {
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
    // 驗證規則與後端 ChangePasswordRequest 一致
    async changePassword(oldPassword, newPassword, newPasswordConfirmation) {
      const profile = getStoredProfile()

      if (!getToken() || !profile) {
        throw new ApiError(401, '尚未登入或登入已失效')
      }

      const fail = (field: string, message: string): never => {
        throw new ApiError(422, message, { [field]: [message] })
      }

      if (oldPassword !== currentPassword(profile.number)) {
        fail('oldPassword', '舊密碼錯誤')
      }

      if (newPassword.length < PASSWORD_MIN_LENGTH) {
        fail('newPassword', `新密碼至少需要 ${PASSWORD_MIN_LENGTH} 個字元`)
      }

      if (!isValidPassword(newPassword)) {
        fail('newPassword', '新密碼需包含大寫、小寫、數字與特殊符號各一個')
      }

      if (newPassword === oldPassword) {
        fail('newPassword', '新密碼不可與舊密碼相同')
      }

      if (newPassword !== newPasswordConfirmation) {
        fail('newPasswordConfirmation', '兩次輸入的新密碼不一致')
      }

      sessionStorage.setItem(`${PASSWORD_KEY_PREFIX}${profile.number}`, newPassword)
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
  attendance: {
    // 唯讀；Demo 沒有後端，日期篩選、排序、分頁在這裡模擬，規則與 AttendanceController::index 一致
    async list(query) {
      if (query.dateFrom && query.dateTo && query.dateTo < query.dateFrom) {
        throw new ApiError(422, '結束日期不可早於開始日期', { dateTo: ['結束日期不可早於開始日期'] })
      }

      const direction = query.order === 'asc' ? 1 : -1
      const text = query.search.trim().toLowerCase()
      // 出勤紀錄不帶部門，篩選時用精靈編號對照
      const departmentIdOf = (number: string) => elves.find((elf) => elf.number === number)?.departmentId

      const sorted = attendanceRecords()
        // clockIn 前 10 碼就是上班日期（YYYY-MM-DD），字串比較即可
        .filter(
          (item) =>
            (!query.dateFrom || item.clockIn.slice(0, 10) >= query.dateFrom) &&
            (!query.dateTo || item.clockIn.slice(0, 10) <= query.dateTo) &&
            (query.departmentId === null || departmentIdOf(item.elfNumber) === query.departmentId) &&
            (text === '' ||
              item.elfNumber.toLowerCase().includes(text) ||
              item.elfName.toLowerCase().includes(text)),
        )
        .sort((a, b) => {
          const byTime = b.clockIn.localeCompare(a.clockIn) || b.id - a.id

          if (query.sort === 'number') {
            return direction * a.elfNumber.localeCompare(b.elfNumber) || byTime
          }

          if (query.sort === 'clockOut') {
            return direction * a.clockOut.localeCompare(b.clockOut) || byTime
          }

          if (query.sort === 'workMinutes') {
            return direction * (a.workMinutes - b.workMinutes) || byTime
          }

          return direction * a.clockIn.localeCompare(b.clockIn) || byTime
        })

      return {
        items: sorted.slice((query.page - 1) * query.perPage, query.page * query.perPage),
        total: sorted.length,
        page: Math.max(1, query.page),
        perPage: query.perPage,
        lastPage: Math.max(1, Math.ceil(sorted.length / query.perPage)),
      }
    },
  },
  complaint: {
    // 新的在前（申訴日期，同日以 id），與後端一致；分頁、篩選在這裡模擬
    async list(query) {
      const filtered = complaintStore
        .read()
        .filter((item) => query.status === null || item.status === query.status)
        .sort((a, b) => b.filedAt.localeCompare(a.filedAt) || b.id - a.id)

      return {
        items: filtered.slice((query.page - 1) * query.perPage, query.page * query.perPage),
        total: filtered.length,
        page: Math.max(1, query.page),
        perPage: query.perPage,
        lastPage: Math.max(1, Math.ceil(filtered.length / query.perPage)),
      }
    },
    async close(id, resolution) {
      const items = complaintStore.read()
      const current = items.find((item) => item.id === id)

      if (!current) {
        throw new ApiError(404, '找不到這筆申訴')
      }

      if (current.status === '已結案') {
        throw new ApiError(409, '這筆申訴已經結案')
      }

      if (resolution.trim() === '') {
        throw new ApiError(422, '後續處理說明必填', { resolution: ['後續處理說明必填'] })
      }

      // 處理人由登入者帶入
      const closed: Complaint = {
        ...current,
        status: '已結案',
        resolution: resolution.trim(),
        handler: getStoredProfile()?.name ?? null,
      }
      complaintStore.write(items.map((item) => (item.id === id ? closed : item)))
      return closed
    },
    async create(input) {
      const profile = getStoredProfile()
      const target = rosterStore.read().find((item) => item.number === input.elfNumber)

      if (!target) {
        throw new ApiError(422, '找不到這個精靈編號', { elfNumber: ['找不到這個精靈編號'] })
      }

      if (target.number === profile?.number) {
        throw new ApiError(422, '不能申訴自己', { elfNumber: ['不能申訴自己'] })
      }

      const items = complaintStore.read()
      const created: Complaint = {
        id: Math.max(0, ...items.map((item) => item.id)) + 1,
        elfNumber: target.number,
        elfName: target.name,
        filedAt: new Date().toLocaleDateString('sv-SE'),
        reason: input.reason.trim(),
        status: '處理中',
        resolution: null,
        handler: null,
      }
      complaintStore.write([...items, created])
      return { id: created.id, elfNumber: created.elfNumber, elfName: created.elfName }
    },
  },
  department: {
    async list() {
      return departments
    },
  },
  elf: {
    // Demo 沒有後端，搜尋、篩選、排序、分頁在這裡模擬，規則與 ElfController::index 一致
    async list(query) {
      const text = query.search.trim().toLowerCase()
      const direction = query.order === 'asc' ? 1 : -1

      const filtered = rosterStore
        .read()
        .filter(
          (elf) =>
            (query.departmentId === null || elf.departmentId === query.departmentId) &&
            (text === '' ||
              elf.number.toLowerCase().includes(text) ||
              elf.name.toLowerCase().includes(text)),
        )

      const byNumber = (a: StoredElf, b: StoredElf) => a.number.localeCompare(b.number)

      filtered.sort((a, b) => {
        if (query.sort === 'department') {
          return direction * (a.departmentId - b.departmentId) || byNumber(a, b)
        }

        if (query.sort === 'seniority') {
          // 年資越高＝到職日越早，方向與到職日相反
          return -direction * a.hiredAt.localeCompare(b.hiredAt) || byNumber(a, b)
        }

        return direction * byNumber(a, b)
      })

      const lastPage = Math.max(1, Math.ceil(filtered.length / query.perPage))
      // 與後端一致：不修正超出範圍的頁數，回傳空頁
      const page = Math.max(1, query.page)

      return {
        items: filtered.slice((page - 1) * query.perPage, page * query.perPage).map(withLastAttended),
        total: filtered.length,
        page,
        perPage: query.perPage,
        lastPage,
      }
    },
    async create(input) {
      const items = rosterStore.read()

      // 與後端 Elf::nextNumber 一致：E 開頭編號的最大數字 + 1，至少三位數
      const maxNumber = Math.max(0, ...items.map((item) => Number(item.number.slice(1)) || 0))

      const created: StoredElf = {
        ...input,
        number: `E${String(maxNumber + 1).padStart(3, '0')}`,
        id: Math.max(0, ...items.map((item) => item.id)) + 1,
        department: departmentName(input.departmentId) ?? '',
      }
      rosterStore.write([...items, created])
      return withLastAttended(created)
    },
    async update(id, input) {
      const current = rosterStore.read().find((item) => item.id === id)

      if (!current) {
        throw new ApiError(404, '找不到這名精靈')
      }

      // 精靈編號與到職日不可修改，沿用原值
      const updated: StoredElf = {
        ...input,
        id,
        number: current.number,
        hiredAt: current.hiredAt,
        // 請假狀態由請假單決定，不能手動改
        status: current.status === '請假' ? '請假' : (input.status ?? current.status),
        department: departmentName(input.departmentId) ?? '',
      }
      rosterStore.write(rosterStore.read().map((item) => (item.id === id ? updated : item)))
      return withLastAttended(updated)
    },
    async remove(id) {
      if (getStoredProfile()?.id === id) {
        throw new ApiError(422, '不能刪除自己')
      }

      rosterStore.write(rosterStore.read().filter((item) => item.id !== id))
    },
  },
  statics: {
    async annual() {
      // 與 API 行為一致：取最新 10 年，由舊到新
      return (annualData as AnnualStatic[]).slice(-10)
    },
  },
}
