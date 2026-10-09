<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  page: number
  lastPage: number
  total: number
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

// 頁碼最多顯示目前頁前後各 2 頁，頭尾頁固定顯示，中間省略（null）
const pageButtons = computed(() => {
  const pages = new Set([1, props.lastPage])

  for (let i = props.page - 2; i <= props.page + 2; i++) {
    if (i >= 1 && i <= props.lastPage) {
      pages.add(i)
    }
  }

  const sorted = [...pages].sort((a, b) => a - b)
  const result: (number | null)[] = []

  sorted.forEach((value, index) => {
    if (index > 0 && value - sorted[index - 1] > 1) {
      result.push(null)
    }
    result.push(value)
  })

  return result
})

const go = (target: number) => emit('change', Math.min(Math.max(1, target), props.lastPage))
</script>

<template>
  <nav v-if="total > 0" class="pager" aria-label="分頁">
    <p class="pager__total">共 {{ total }} 筆，第 {{ page }} / {{ lastPage }} 頁</p>
    <div class="pager__buttons">
      <button type="button" class="button" :disabled="page <= 1" @click="go(page - 1)">上一頁</button>
      <template v-for="(item, index) in pageButtons" :key="index">
        <span v-if="item === null" class="pager__gap" aria-hidden="true">…</span>
        <button
          v-else
          type="button"
          class="button"
          :class="{ 'button--primary': item === page }"
          :aria-current="item === page ? 'page' : undefined"
          @click="go(item)"
        >
          {{ item }}
        </button>
      </template>
      <button type="button" class="button" :disabled="page >= lastPage" @click="go(page + 1)">
        下一頁
      </button>
    </div>
  </nav>
</template>

<style scoped>
.pager {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  margin-top: 1rem;
}

.pager__total {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.pager__buttons {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.375rem;
}

.pager__buttons .button {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}

.pager__gap {
  padding: 0 0.25rem;
  color: var(--color-text-muted);
}
</style>
