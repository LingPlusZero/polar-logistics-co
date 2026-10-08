<script setup lang="ts">
import { padTime as pad, useDeadlineCountdown } from '@shared/composables/useDeadlineCountdown'

const { parts } = useDeadlineCountdown()
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
