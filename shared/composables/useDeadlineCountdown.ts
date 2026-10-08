import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

// 交貨期限：12/24 夜間送完，準時以「12/25 零點前抵達」為準（docs/website.md 數據列）
const DEADLINE_MONTH = 11 // JavaScript 的月份從 0 開始，11 = 12 月
const DEADLINE_DAY = 25

// 官網與管理系統共用的倒數計時，每秒更新
export function useDeadlineCountdown() {
  const now = ref(Date.now())
  let timer = 0

  // 今年的期限已過就改算明年，倒數才不會停在 0
  const deadline = computed(() => {
    const current = new Date(now.value)
    let target = new Date(current.getFullYear(), DEADLINE_MONTH, DEADLINE_DAY)

    if (target.getTime() <= now.value) {
      target = new Date(current.getFullYear() + 1, DEADLINE_MONTH, DEADLINE_DAY)
    }

    return target.getTime()
  })

  const parts = computed(() => {
    const remaining = Math.max(0, deadline.value - now.value)
    const totalSeconds = Math.floor(remaining / 1000)

    return {
      days: Math.floor(totalSeconds / 86400),
      hours: Math.floor((totalSeconds % 86400) / 3600),
      minutes: Math.floor((totalSeconds % 3600) / 60),
      seconds: totalSeconds % 60,
    }
  })

  onMounted(() => {
    timer = window.setInterval(() => {
      now.value = Date.now()
    }, 1000)
  })

  onBeforeUnmount(() => window.clearInterval(timer))

  return { parts }
}

export const padTime = (value: number) => String(value).padStart(2, '0')
