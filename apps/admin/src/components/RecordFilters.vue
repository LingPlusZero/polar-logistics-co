<script setup lang="ts">
import type { Department } from '@shared/api'

defineProps<{
  departments: Department[]
  searchLabel?: string
}>()

// 篩選條件由父層持有，這裡只負責畫面；搜尋文字的 debounce 與重新載入由父層處理
const keyword = defineModel<string>('keyword', { required: true })
const departmentId = defineModel<number | null>('departmentId', { required: true })
const dateFrom = defineModel<string>('dateFrom', { required: true })
const dateTo = defineModel<string>('dateTo', { required: true })

const clearDates = () => {
  dateFrom.value = ''
  dateTo.value = ''
}
</script>

<template>
  <form class="filters" role="search" @submit.prevent>
    <label class="filter">
      <span class="filter__label">搜尋</span>
      <input
        v-model="keyword"
        class="field__input"
        type="search"
        :placeholder="searchLabel ?? '精靈編號或姓名'"
        autocomplete="off"
      />
    </label>

    <label class="filter">
      <span class="filter__label">部門</span>
      <select v-model="departmentId" class="field__input">
        <option :value="null">全部部門</option>
        <option v-for="department in departments" :key="department.id" :value="department.id">
          {{ department.name }}
        </option>
      </select>
    </label>

    <label class="filter">
      <span class="filter__label">日期（起）</span>
      <input v-model="dateFrom" class="field__input" type="date" :max="dateTo || undefined" />
    </label>

    <label class="filter">
      <span class="filter__label">日期（迄）</span>
      <input v-model="dateTo" class="field__input" type="date" :min="dateFrom || undefined" />
    </label>

    <button
      type="button"
      class="button filters__clear"
      :disabled="!dateFrom && !dateTo"
      @click="clearDates"
    >
      清除日期
    </button>

    <!-- 各頁自己的額外條件（例如申訴狀態） -->
    <slot />
  </form>
</template>

<style scoped>
.filters {
  display: grid;
  gap: 0.75rem 1rem;
  align-items: end;
  margin-top: 1.25rem;
}

.filters__clear {
  justify-self: start;
  padding: 0.625rem 1.25rem;
}

@media (min-width: 48em) {
  .filters {
    grid-template-columns: repeat(4, 12rem) auto;
  }
}
</style>
