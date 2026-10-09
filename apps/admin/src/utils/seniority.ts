// 年資由到職日計算（資料庫不存），以整月為單位
export function seniorityMonths(hiredAt: string, now = new Date()): number {
  const [year, month, day] = hiredAt.split('-').map(Number)
  let months = (now.getFullYear() - year) * 12 + (now.getMonth() + 1 - month)

  // 當月還沒到到職日的日期就還不滿一個月
  if (now.getDate() < day) {
    months -= 1
  }

  return Math.max(0, months)
}

export function formatSeniority(hiredAt: string, now = new Date()): string {
  const months = seniorityMonths(hiredAt, now)
  const years = Math.floor(months / 12)
  const rest = months % 12

  if (years === 0 && rest === 0) {
    return '未滿 1 個月'
  }

  if (years === 0) {
    return `${rest} 個月`
  }

  return rest === 0 ? `${years} 年` : `${years} 年 ${rest} 個月`
}
