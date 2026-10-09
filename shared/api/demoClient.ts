import type { ApiClient } from './client'
import type { AnnualStatic, Attendance, Career, Complaint, Department, Elf, ElfProfile, Leave, LeaveListQuery, Page, PersonOption, Reindeer } from './types'
import annualData from './demo/data/annual.json'
import careerData from './demo/data/career.json'
import complaintData from './demo/data/complaint.json'
import departmentData from './demo/data/department.json'
import elfData from './demo/data/elf.json'
import leaveData from './demo/data/leave.json'
import reindeerData from './demo/data/reindeer.json'
import rosterData from './demo/data/roster.json'
import { generateAttendance } from './demo/attendance'
import { ApiError } from './errors'
import { addDays, leaveDays, leaveEndDate, PEAK_SEASON_MESSAGE, touchesPeakSeason } from './leave'
import { PASSWORD_MIN_LENGTH, isValidPassword } from './password'
import { nextMaintenanceDate } from './reindeer'
import { getStoredProfile, getToken } from './session'

// 本地日期 YYYY-MM-DD
const todayString = () => new Date().toLocaleDateString('sv-SE')

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

  // 「請假」由請假單決定：核准且今天在假期內；「可能失蹤」優先（與後端 Elf::displayStatus 一致）
  const today = todayString()
  const onLeave = leaveStore
    .read()
    .some(
      (leave) =>
        !leave.reindeerNumber &&
        leave.elfNumber === elf.number &&
        leave.status === '核准' &&
        leave.startDate <= today &&
        leave.endDate >= today,
    )
  const status = elf.status !== '可能失蹤' && onLeave ? '請假' : elf.status

  return { ...elf, status, lastAttendedAt: last ? last.slice(0, 10) : null }
}
// 名冊存的欄位不含最後出勤日（它由出勤紀錄算出，輸出時才帶上）
type StoredElf = Omit<Elf, 'lastAttendedAt'>
const rosterStore = createStore<StoredElf>('demo:roster', rosterData as StoredElf[])
// 預設申訴紀錄的日期以「距今幾天前」存，載入時換算成日期，預設的最近一週才有資料
type ComplaintSeed = Omit<Complaint, 'filedAt'> & { daysAgo: number }

const complaintSeed: Complaint[] = (complaintData as ComplaintSeed[]).map(({ daysAgo, ...rest }) => {
  const date = new Date()
  date.setDate(date.getDate() - daysAgo)
  return { ...rest, filedAt: date.toLocaleDateString('sv-SE') }
})
const complaintStore = createStore<Complaint>('demo:complaint', complaintSeed)

// 預設假單的日期以「距今幾天前」存（負數為未來），載入時換算成日期；與 LeaveRequestSeeder 一致
type LeaveSeed = Pick<Leave, 'id' | 'elfNumber' | 'elfName' | 'leaveType' | 'rejectReason'> & {
  // 照護專員代馴鹿請假的假單才有
  reindeerNumber?: string
  reindeerName?: string
  startAgo: number
  appliedAgo: number
  reviewer: string | null
  reviewedAgo: number | null
}

const daysFromToday = (daysAgo: number) => addDays(todayString(), -daysAgo)

const leaveSeed: Leave[] = (leaveData as LeaveSeed[]).map((item) => {
  const startDate = daysFromToday(item.startAgo)

  return {
    id: item.id,
    elfNumber: item.elfNumber,
    elfName: item.elfName,
    reindeerNumber: item.reindeerNumber ?? null,
    reindeerName: item.reindeerName ?? null,
    leaveType: item.leaveType,
    days: leaveDays(item.leaveType),
    startDate,
    endDate: leaveEndDate(startDate, item.leaveType),
    appliedAt: daysFromToday(item.appliedAgo),
    status: item.reviewer === null ? '審核中' : item.rejectReason ? '駁回' : '核准',
    reviewedAt: item.reviewedAgo === null ? null : daysFromToday(item.reviewedAgo),
    reviewer: item.reviewer,
    rejectReason: item.rejectReason,
  }
})
const leaveStore = createStore<Leave>('demo:leave', leaveSeed)

// 動力單位：預設資料的上次保養日以「距今幾天前」存，載入時換算成日期；與 ReindeerSeeder 一致
type ReindeerSeed = Pick<Reindeer, 'id' | 'number' | 'name' | 'hiredAt' | 'note'> & {
  maintainedAgo: number
  caretakerNumber: string
}

// 寫入只存編號之外的原始欄位；下次保養日期與照護專員姓名輸出時才算（withReindeerDerived）
type StoredReindeer = Omit<Reindeer, 'nextMaintenanceAt' | 'caretaker'>

const reindeerSeed: StoredReindeer[] = (reindeerData as ReindeerSeed[]).map((item) => ({
  id: item.id,
  number: item.number,
  name: item.name,
  hiredAt: item.hiredAt,
  lastMaintainedAt: daysFromToday(item.maintainedAgo),
  caretakerId: elves.find((elf) => elf.number === item.caretakerNumber)?.id ?? null,
  note: item.note,
}))
const reindeerStore = createStore<StoredReindeer>('demo:reindeer', reindeerSeed)

const withReindeerDerived = (item: StoredReindeer): Reindeer => ({
  ...item,
  nextMaintenanceAt: nextMaintenanceDate(item.lastMaintainedAt),
  caretaker: elves.find((elf) => elf.id === item.caretakerId)?.name ?? null,
})

// 照護專員必須是馴鹿管理部的精靈（與後端 ReindeerRequest 一致）
const CARETAKER_DEPARTMENT = '馴鹿管理部'
const caretakerError = (caretakerId: number) =>
  elves.find((elf) => elf.id === caretakerId)?.department === CARETAKER_DEPARTMENT
    ? null
    : new ApiError(422, `照護專員必須是${CARETAKER_DEPARTMENT}的精靈`, {
        caretakerId: [`照護專員必須是${CARETAKER_DEPARTMENT}的精靈`],
      })

// 審核範圍（與後端 LeaveRequest::scopeReviewableBy 一致）：部長審自己部門的非部長，副聖誕老人審各部長與自己
const canReviewLeave = (reviewer: ElfProfile | null, leave: Leave) => {
  const applicant = elves.find((elf) => elf.number === leave.elfNumber)

  if (!reviewer || !applicant) {
    return false
  }

  if (reviewer.rank === '部長') {
    return applicant.departmentId === reviewer.departmentId && applicant.rank !== '部長'
  }

  return reviewer.rank === '副聖誕老人' && (applicant.rank === '部長' || applicant.number === reviewer.number)
}

// 審核：只能審核範圍內、且尚在審核中的假單；審核人與審核日由系統帶入
const decideLeave = async (id: number, status: '核准' | '駁回', rejectReason: string | null = null): Promise<Leave> => {
  const profile = getStoredProfile()
  const items = leaveStore.read()
  const current = items.find((leave) => leave.id === id)

  if (!current) {
    throw new ApiError(404, '找不到這張假單')
  }

  if (!canReviewLeave(profile, current)) {
    throw new ApiError(403, '沒有權限審核這張假單')
  }

  if (current.status !== '審核中') {
    throw new ApiError(409, '這張假單已經審核過')
  }

  const decided: Leave = {
    ...current,
    status,
    reviewedAt: todayString(),
    reviewer: profile?.name ?? null,
    rejectReason,
  }
  leaveStore.write(items.map((leave) => (leave.id === id ? decided : leave)))
  return decided
}

// 與後端一致：申請日新到舊，同日以 id
const pageOfLeaves = (items: Leave[], query: LeaveListQuery): Page<Leave> => {
  if (query.dateFrom && query.dateTo && query.dateTo < query.dateFrom) {
    throw new ApiError(422, '結束日期不可早於開始日期', { dateTo: ['結束日期不可早於開始日期'] })
  }

  // 日期以申請日期篩選，起迄含當天
  const sorted = items
    .filter(
      (leave) =>
        (query.status === null || leave.status === query.status) &&
        (!query.dateFrom || leave.appliedAt >= query.dateFrom) &&
        (!query.dateTo || leave.appliedAt <= query.dateTo),
    )
    .sort((a, b) => b.appliedAt.localeCompare(a.appliedAt) || b.id - a.id)

  return {
    items: sorted.slice((query.page - 1) * query.perPage, query.page * query.perPage),
    total: sorted.length,
    page: Math.max(1, query.page),
    perPage: query.perPage,
    lastPage: Math.max(1, Math.ceil(sorted.length / query.perPage)),
  }
}

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
    // 新的在前（申訴日期，同日以 id），與後端一致；搜尋、篩選、分頁在這裡模擬
    async list(query) {
      if (query.dateFrom && query.dateTo && query.dateTo < query.dateFrom) {
        throw new ApiError(422, '結束日期不可早於開始日期', { dateTo: ['結束日期不可早於開始日期'] })
      }

      const text = query.search.trim().toLowerCase()
      // 申訴紀錄不帶部門，篩選時用被申訴人的精靈編號對照
      const departmentIdOf = (number: string) => elves.find((elf) => elf.number === number)?.departmentId

      const filtered = complaintStore
        .read()
        .filter(
          (item) =>
            (query.status === null || item.status === query.status) &&
            (!query.dateFrom || item.filedAt >= query.dateFrom) &&
            (!query.dateTo || item.filedAt <= query.dateTo) &&
            (query.departmentId === null || departmentIdOf(item.elfNumber) === query.departmentId) &&
            (text === '' ||
              item.elfNumber.toLowerCase().includes(text) ||
              item.elfName.toLowerCase().includes(text)),
        )
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
  leave: {
    async mine(query) {
      const profile = getStoredProfile()
      return pageOfLeaves(
        leaveStore.read().filter((leave) => leave.elfNumber === profile?.number),
        query,
      )
    },
    async review(query) {
      const profile = getStoredProfile()
      return pageOfLeaves(
        leaveStore.read().filter((leave) => canReviewLeave(profile, leave)),
        query,
      )
    },
    // 全部精靈的請假紀錄；搜尋與部門針對申請人（與後端 LeaveController::records 一致）
    async records(query) {
      const text = query.search.trim().toLowerCase()
      const departmentIdOf = (number: string) => elves.find((elf) => elf.number === number)?.departmentId

      return pageOfLeaves(
        leaveStore
          .read()
          .filter(
            (leave) =>
              !leave.reindeerNumber &&
              (query.departmentId === null || departmentIdOf(leave.elfNumber) === query.departmentId) &&
              (text === '' ||
                leave.elfNumber.toLowerCase().includes(text) ||
                leave.elfName.toLowerCase().includes(text)),
          ),
        query,
      )
    },
    async apply(input) {
      const profile = getStoredProfile()

      if (!profile) {
        throw new ApiError(401, '尚未登入')
      }

      const today = todayString()

      if (input.startDate < today) {
        throw new ApiError(422, '請假起日不可早於今天', { startDate: ['請假起日不可早於今天'] })
      }

      const endDate = leaveEndDate(input.startDate, input.leaveType)

      if (touchesPeakSeason(input.startDate, endDate)) {
        throw new ApiError(422, PEAK_SEASON_MESSAGE, { startDate: [PEAK_SEASON_MESSAGE] })
      }

      // 只有該馴鹿的照護專員能代請（不存在的馴鹿與別人的馴鹿同樣回覆）
      const reindeer =
        input.reindeerId == null
          ? null
          : reindeerStore.read().find((item) => item.id === input.reindeerId && item.caretakerId === profile.id)

      if (input.reindeerId != null && !reindeer) {
        throw new ApiError(422, '只有照護專員能代馴鹿請假', { reindeerId: ['只有照護專員能代馴鹿請假'] })
      }

      const items = leaveStore.read()
      // 每個假不能重疊；被駁回的假單不佔用日期。精靈自己與每隻馴鹿的假單分開計算
      const overlapping = items.some(
        (leave) =>
          leave.elfNumber === profile.number &&
          (leave.reindeerNumber ?? null) === (reindeer?.number ?? null) &&
          leave.status !== '駁回' &&
          leave.startDate <= endDate &&
          leave.endDate >= input.startDate,
      )

      if (overlapping) {
        throw new ApiError(422, '這段期間已經有請假單，不能重疊', {
          startDate: ['這段期間已經有請假單，不能重疊'],
        })
      }

      const created: Leave = {
        id: Math.max(0, ...items.map((leave) => leave.id)) + 1,
        elfNumber: profile.number,
        elfName: profile.name,
        reindeerNumber: reindeer?.number ?? null,
        reindeerName: reindeer?.name ?? null,
        leaveType: input.leaveType,
        days: leaveDays(input.leaveType),
        startDate: input.startDate,
        endDate,
        appliedAt: today,
        status: '審核中',
        reviewedAt: null,
        reviewer: null,
        rejectReason: null,
      }
      leaveStore.write([...items, created])
      return created
    },
    async approve(id) {
      return decideLeave(id, '核准')
    },
    async reject(id, reason) {
      if (reason.trim() === '') {
        throw new ApiError(422, '駁回理由必填', { reason: ['駁回理由必填'] })
      }

      return decideLeave(id, '駁回', reason.trim())
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
  reindeer: {
    // 9 隻不分頁；排序規則與 ReindeerController::index 一致（年資越高＝到職日越早）
    async list(query) {
      const direction = query.order === 'asc' ? 1 : -1

      return reindeerStore
        .read()
        .map(withReindeerDerived)
        .sort((a, b) =>
          query.sort === 'seniority'
            ? -direction * a.hiredAt.localeCompare(b.hiredAt) || a.number.localeCompare(b.number)
            : direction * a.number.localeCompare(b.number),
        )
    },
    async create(input) {
      const error = caretakerError(input.caretakerId)

      if (error) {
        throw error
      }

      const items = reindeerStore.read()
      // 與後端 Reindeer::nextNumber 一致：編號的最大數字 + 1，至少兩位數
      const maxNumber = Math.max(0, ...items.map((item) => Number(item.number) || 0))
      const created: StoredReindeer = {
        ...input,
        id: Math.max(0, ...items.map((item) => item.id)) + 1,
        number: String(maxNumber + 1).padStart(2, '0'),
      }
      reindeerStore.write([...items, created])
      return withReindeerDerived(created)
    },
    async update(id, input) {
      const items = reindeerStore.read()
      const current = items.find((item) => item.id === id)

      if (!current) {
        throw new ApiError(404, '找不到這個動力單位')
      }

      const error = caretakerError(input.caretakerId)

      if (error) {
        throw error
      }

      // 編號與到職日不可修改，沿用原值
      const updated: StoredReindeer = { ...input, id, number: current.number, hiredAt: current.hiredAt }
      reindeerStore.write(items.map((item) => (item.id === id ? updated : item)))
      return withReindeerDerived(updated)
    },
    async remove(id) {
      const target = reindeerStore.read().find((item) => item.id === id)
      reindeerStore.write(reindeerStore.read().filter((item) => item.id !== id))

      // 該馴鹿的假單一併刪除
      if (target) {
        leaveStore.write(leaveStore.read().filter((leave) => leave.reindeerNumber !== target.number))
      }
    },
    async caretakers(): Promise<PersonOption[]> {
      return elves
        .filter((elf) => elf.department === CARETAKER_DEPARTMENT)
        .map(({ id, number, name }) => ({ id, number, name }))
        .sort((a, b) => a.number.localeCompare(b.number))
    },
    async mine(): Promise<PersonOption[]> {
      const profile = getStoredProfile()

      return reindeerStore
        .read()
        .filter((item) => item.caretakerId === profile?.id)
        .map(({ id, number, name }) => ({ id, number, name }))
        .sort((a, b) => a.number.localeCompare(b.number))
    },
    async leaves(id) {
      const target = reindeerStore.read().find((item) => item.id === id)

      return leaveStore
        .read()
        .filter((leave) => target !== undefined && leave.reindeerNumber === target.number)
        .sort((a, b) => b.appliedAt.localeCompare(a.appliedAt) || b.id - a.id)
    },
  },
  statics: {
    async annual() {
      // 與 API 行為一致：取最新 10 年，由舊到新
      return (annualData as AnnualStatic[]).slice(-10)
    },
  },
}
