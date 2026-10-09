import type { LeaveType } from './types'

// 假別與天數來源：docs/admin.md「請假申請/審核」，與後端 LeaveType enum 同步
export const LEAVE_TYPES: { type: LeaveType; days: number }[] = [
  { type: '普通病假', days: 1 },
  { type: '魔力枯竭假', days: 2 },
  { type: '被人類目擊後心理創傷假', days: 7 },
]

// 旺季（12 月）不能請假
export const PEAK_MONTH = 12

export const PEAK_SEASON_MESSAGE = '12 月是旺季，不能請假，大家一起撐下去！'

const toDateString = (date: Date) => date.toLocaleDateString('sv-SE')

// 用本地日期的年月日建立 Date，避免 'YYYY-MM-DD' 被當成 UTC 而差一天
const parseDate = (value: string) => {
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, month - 1, day)
}

export const addDays = (value: string, days: number) => {
  const date = parseDate(value)
  date.setDate(date.getDate() + days)
  return toDateString(date)
}

export const leaveDays = (type: LeaveType) => LEAVE_TYPES.find((item) => item.type === type)?.days ?? 1

// 請假迄日（含當天）：起日加上假別天數
export const leaveEndDate = (startDate: string, type: LeaveType) => addDays(startDate, leaveDays(type) - 1)

// 請假期間只要有一天落在 12 月就不行（例如 11/28 起請 7 天）
export function touchesPeakSeason(startDate: string, endDate: string): boolean {
  for (let day = startDate; day <= endDate; day = addDays(day, 1)) {
    if (parseDate(day).getMonth() + 1 === PEAK_MONTH) {
      return true
    }
  }

  return false
}
