const toDateString = (date: Date) => date.toLocaleDateString('sv-SE')

// 預設的篩選日期：最近一週（今天往前 6 天到今天，共 7 天），用瀏覽器本地日期
export function lastWeekRange(now = new Date()): { from: string; to: string } {
  const weekAgo = new Date(now)
  weekAgo.setDate(now.getDate() - 6)

  return { from: toDateString(weekAgo), to: toDateString(now) }
}
