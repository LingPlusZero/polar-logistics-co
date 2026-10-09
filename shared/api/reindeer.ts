// 保養間隔（月），與後端 Reindeer::MAINTENANCE_INTERVAL_MONTHS 同步
export const MAINTENANCE_INTERVAL_MONTHS = 3

// 加月份且月底不溢位（11/30 + 3 個月 = 2/28，不是 3/2），與後端 addMonthsNoOverflow 一致；
// 用本地日期的年月日運算，避免 'YYYY-MM-DD' 被當成 UTC 而差一天
export function addMonthsNoOverflow(value: string, months: number): string {
  const [year, month, day] = value.split('-').map(Number)
  const target = new Date(year, month - 1 + months, 1)
  const lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate()
  target.setDate(Math.min(day, lastDay))

  return target.toLocaleDateString('sv-SE')
}

// 下次保養日期：上次保養後滿 3 個月
export const nextMaintenanceDate = (lastMaintainedAt: string) =>
  addMonthsNoOverflow(lastMaintainedAt, MAINTENANCE_INTERVAL_MONTHS)
