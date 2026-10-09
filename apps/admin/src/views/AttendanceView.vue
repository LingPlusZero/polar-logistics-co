<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Attendance, AttendanceSortKey, Department, SortOrder } from '@shared/api'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import PaginationBar from '../components/PaginationBar.vue'

const PER_PAGE = 10
const SEARCH_DELAY_MS = 300
// 工作時數不足 8 小時的整列會標示底色（docs/admin.md）
const FULL_DAY_MINUTES = 8 * 60

const records = ref<Attendance[]>([])
const total = ref(0)
const lastPage = ref(1)
const page = ref(1)
const isLoading = ref(true)
const loadError = ref('')

const departments = ref<Department[]>([])
const keyword = ref('')
const departmentFilter = ref<number | null>(null)

// 日期以上班日期篩選，起迄皆可留空；預設為最近一週（含今天共 7 天）
const toDateString = (date: Date) => date.toLocaleDateString('sv-SE')
const today = new Date()
const weekAgo = new Date(today)
weekAgo.setDate(today.getDate() - 6)

const dateFrom = ref(toDateString(weekAgo))
const dateTo = ref(toDateString(today))

// null＝還沒點過排序：表頭都不標示，資料依預設的上班時間新到舊
const sortKey = ref<AttendanceSortKey | null>(null)
const sortOrder = ref<SortOrder>('desc')

// 連續操作時舊請求可能比新請求晚回來，只採用最後一次發出的結果
let requestId = 0

const load = async () => {
  const currentId = ++requestId
  isLoading.value = true
  loadError.value = ''

  try {
    const result = await api.attendance.list({
      search: keyword.value,
      departmentId: departmentFilter.value,
      dateFrom: dateFrom.value || null,
      dateTo: dateTo.value || null,
      sort: sortKey.value ?? 'clockIn',
      order: sortOrder.value,
      page: page.value,
      perPage: PER_PAGE,
    })

    if (currentId !== requestId) {
      return
    }

    records.value = result.items
    total.value = result.total
    lastPage.value = result.lastPage
    page.value = result.page
  } catch (error) {
    if (currentId === requestId) {
      loadError.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
    }
  } finally {
    if (currentId === requestId) {
      isLoading.value = false
    }
  }
}

onMounted(async () => {
  departments.value = await api.department.list().catch(() => [])
  await load()
})

// 篩選改變就回到第一頁；搜尋文字等使用者停手再送出
let searchTimer: ReturnType<typeof setTimeout> | undefined

watch(keyword, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    load()
  }, SEARCH_DELAY_MS)
})

watch([departmentFilter, dateFrom, dateTo], () => {
  page.value = 1
  load()
})

onBeforeUnmount(() => clearTimeout(searchTimer))

const clearDates = () => {
  dateFrom.value = ''
  dateTo.value = ''
}

// 點同一欄切換升冪／降冪；換欄時上班、下班時間先看最新的，編號與工作時數先升冪
// （工作時數升冪＝先看工時最短的，方便找出不足 8 小時的日子）
const toggleSort = (key: AttendanceSortKey) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = key === 'clockIn' || key === 'clockOut' ? 'desc' : 'asc'
  }

  page.value = 1
  load()
}

const ariaSort = (key: AttendanceSortKey) =>
  sortKey.value === key ? (sortOrder.value === 'asc' ? 'ascending' : 'descending') : 'none'

const sortArrow = (key: AttendanceSortKey) =>
  sortKey.value === key ? (sortOrder.value === 'asc' ? '▲' : '▼') : '↕'

const goToPage = (target: number) => {
  page.value = target
  load()
}

const formatWorkTime = (minutes: number) => {
  const hours = Math.floor(minutes / 60)
  const rest = minutes % 60
  return rest === 0 ? `${hours} 小時` : `${hours} 小時 ${rest} 分`
}
</script>

<template>
  <section>
    <h1 class="title">精靈出勤紀錄</h1>

    <form class="filters" role="search" @submit.prevent>
      <label class="filter">
        <span class="filter__label">搜尋</span>
        <input
          v-model="keyword"
          class="field__input"
          type="search"
          placeholder="精靈編號或姓名"
          autocomplete="off"
        />
      </label>

      <label class="filter">
        <span class="filter__label">部門</span>
        <select v-model="departmentFilter" class="field__input">
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
    </form>

    <p class="legend">
      <span class="legend__swatch" aria-hidden="true"></span>
      底色標示：工作時數不足 8 小時
    </p>

    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col" :aria-sort="ariaSort('number')">
              <button type="button" class="sort" @click="toggleSort('number')">
                精靈編號 <span aria-hidden="true">{{ sortArrow('number') }}</span>
              </button>
            </th>
            <th scope="col" :aria-sort="ariaSort('clockIn')">
              <button type="button" class="sort" @click="toggleSort('clockIn')">
                上班時間 <span aria-hidden="true">{{ sortArrow('clockIn') }}</span>
              </button>
            </th>
            <th scope="col" :aria-sort="ariaSort('clockOut')">
              <button type="button" class="sort" @click="toggleSort('clockOut')">
                下班時間 <span aria-hidden="true">{{ sortArrow('clockOut') }}</span>
              </button>
            </th>
            <th scope="col" :aria-sort="ariaSort('workMinutes')">
              <button type="button" class="sort" @click="toggleSort('workMinutes')">
                工作時數 <span aria-hidden="true">{{ sortArrow('workMinutes') }}</span>
              </button>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="record in records"
            :key="record.id"
            :class="{ 'row--short': record.workMinutes < FULL_DAY_MINUTES }"
          >
            <td>{{ record.elfNumber }}　{{ record.elfName }}</td>
            <td>{{ record.clockIn }}</td>
            <td>{{ record.clockOut }}</td>
            <td>{{ formatWorkTime(record.workMinutes) }}</td>
          </tr>
          <tr v-if="!isLoading && records.length === 0">
            <td colspan="4" class="table-empty">沒有符合條件的出勤紀錄</td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar :page="page" :last-page="lastPage" :total="total" @change="goToPage" />
  </section>
</template>

<style scoped>
.title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.filters {
  display: grid;
  gap: 0.75rem 1rem;
  align-items: end;
  margin-top: 1.25rem;
}

.filter__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.filters__clear {
  justify-self: start;
  padding: 0.625rem 1.25rem;
}

.legend {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.legend__swatch {
  width: 1rem;
  height: 1rem;
  background: #fff6d0;
  border: 1px solid var(--color-ice-dark);
}

.row--short {
  background: #fff6d0;
}

@media (min-width: 48em) {
  .filters {
    grid-template-columns: repeat(4, 12rem) auto;
  }
}
</style>
