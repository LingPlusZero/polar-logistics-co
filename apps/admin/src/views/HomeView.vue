<script setup lang="ts">
import { padTime as pad, useDeadlineCountdown } from '@shared/composables/useDeadlineCountdown'
import { computed } from 'vue'
import SnowBackdrop from '../components/SnowBackdrop.vue'
import { useAuth } from '../composables/useAuth'
import { filterMenu, isGroup } from '../config/menu'

const { profile, can } = useAuth()
const { parts } = useDeadlineCountdown()

const UNITS = [
  { key: 'days', label: '天' },
  { key: 'hours', label: '時' },
  { key: 'minutes', label: '分' },
  { key: 'seconds', label: '秒' },
] as const

const today = new Intl.DateTimeFormat('zh-TW', {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  weekday: 'long',
}).format(new Date())

// 常用功能：把有權限的選單項目攤平成捷徑
const shortcuts = computed(() =>
  filterMenu(can).flatMap((item) =>
    isGroup(item)
      ? item.children.map((child) => ({ ...child, group: item.label }))
      : [{ ...item, group: '管理' }],
  ),
)
</script>

<template>
  <div class="home">
    <section class="hero">
      <SnowBackdrop />

      <div class="hero__content">
        <p class="hero__date">{{ today }}</p>
        <h1 class="hero__title">{{ profile?.name }}，您好</h1>
        <p class="hero__meta">{{ profile?.department }}　{{ profile?.rank }}　{{ profile?.number }}</p>

        <div class="countdown" role="timer" aria-label="距離 12/24 交貨期限">
          <h2 class="countdown__title">距離 12/24 交貨期限</h2>
          <div class="countdown__units">
            <div v-for="unit in UNITS" :key="unit.key" class="countdown__unit">
              <!-- key 隨數值改變，讓數字每次更新都重播進場動畫 -->
              <strong :key="parts[unit.key]" class="countdown__value">
                {{ unit.key === 'days' ? parts[unit.key] : pad(parts[unit.key]) }}
              </strong>
              <span class="countdown__label">{{ unit.label }}</span>
            </div>
          </div>
          <p class="countdown__note">交貨期限為全年唯一，沒有任何延期可能。</p>
        </div>
      </div>
    </section>

    <section v-if="shortcuts.length > 0" class="shortcuts">
      <h2 class="shortcuts__title">常用功能</h2>
      <div class="shortcuts__list">
        <RouterLink v-for="shortcut in shortcuts" :key="shortcut.path" :to="shortcut.path" class="shortcut">
          <span class="shortcut__group">{{ shortcut.group }}</span>
          <span class="shortcut__label">{{ shortcut.label }}</span>
        </RouterLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
.hero {
  position: relative;
  overflow: hidden;
  color: var(--color-ice);
  background: linear-gradient(160deg, var(--color-navy) 0%, var(--color-navy-light) 100%);
}

.hero__content {
  position: relative;
  /* 底部留空間給右下角的聖誕樹，避免蓋到文字 */
  padding: 1.75rem 1.25rem 6rem;
}

.hero__date {
  font-size: 0.8125rem;
  letter-spacing: 0.08em;
  color: var(--color-ice-dark);
}

.hero__title {
  margin-top: 0.25rem;
  font-size: 1.75rem;
  line-height: 1.4;
}

.hero__meta {
  color: var(--color-ice-dark);
}

.countdown {
  margin-top: 1.75rem;
}

.countdown__title {
  font-size: 1rem;
  font-weight: 500;
  color: var(--color-ice-dark);
}

.countdown__units {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  margin-top: 0.75rem;
  max-width: 36rem;
}

.countdown__unit {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.875rem 0.5rem;
  background: rgb(244 248 251 / 0.08);
  border: 1px solid rgb(244 248 251 / 0.18);
  /* 磨砂玻璃效果，讓背後的雪花若隱若現 */
  backdrop-filter: blur(4px);
}

/* 固定寬度數字，避免每秒跳動時版面左右抖動 */
.countdown__value {
  font-size: 2.5rem;
  line-height: 1.1;
  font-variant-numeric: tabular-nums;
  animation: tick-in 0.35s ease-out;
}

.countdown__label {
  color: var(--color-ice-dark);
}

.countdown__note {
  margin-top: 1rem;
  font-size: 0.8125rem;
  color: var(--color-ice-dark);
}

@keyframes tick-in {
  from {
    opacity: 0.2;
    transform: translateY(-0.375rem);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

.shortcuts {
  margin-top: 2rem;
}

.shortcuts__title {
  font-size: 1.125rem;
}

.shortcuts__list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.shortcut {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 1rem;
  text-decoration: none;
  background: #fff;
  border: 1px solid var(--color-ice-dark);
  border-top: 3px solid var(--color-navy);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.shortcut:hover,
.shortcut:focus-visible {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgb(11 37 69 / 0.12);
}

.shortcut__group {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.shortcut__label {
  font-weight: 700;
}

@media (min-width: 48em) {
  .hero__content {
    padding: 2.5rem 2.5rem 5.5rem;
  }

  .hero__title {
    font-size: 2.25rem;
  }

  .countdown__units {
    grid-template-columns: repeat(4, 1fr);
  }

  .countdown__value {
    font-size: 3rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .countdown__value {
    animation: none;
  }

  .shortcut {
    transition: none;
  }
}
</style>
