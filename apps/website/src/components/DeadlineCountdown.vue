<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

// 交貨期限：12/24 夜間送完，準時以「12/25 零點前抵達」為準（docs/website.md 數據列）
const DEADLINE_MONTH = 11 // JavaScript 的月份從 0 開始，11 = 12 月
const DEADLINE_DAY = 25

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

const pad = (value: number) => String(value).padStart(2, '0')

onMounted(() => {
  timer = window.setInterval(() => {
    now.value = Date.now()
  }, 1000)
})

onBeforeUnmount(() => window.clearInterval(timer))
</script>

<template>
  <span class="countdown" role="timer">
    <span class="countdown__unit"><strong>{{ parts.days }}</strong> 天</span>
    <span class="countdown__unit"><strong>{{ pad(parts.hours) }}</strong> 時</span>
    <span class="countdown__unit"><strong>{{ pad(parts.minutes) }}</strong> 分</span>
    <span class="countdown__unit"><strong>{{ pad(parts.seconds) }}</strong> 秒</span>
  </span>
</template>

<style scoped>
.countdown {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.25rem 0.75rem;
}

.countdown__unit {
  font-size: 0.875rem;
  color: var(--color-ice-dark);
}

/* 固定寬度數字，避免每秒跳動時版面左右抖動 */
.countdown strong {
  font-size: 1.125rem;
  color: var(--color-ice);
  font-variant-numeric: tabular-nums;
}
</style>
