<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Department, Leave, LeaveStatus } from '@shared/api'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import PaginationBar from '../components/PaginationBar.vue'
import RecordFilters from '../components/RecordFilters.vue'
import { lastWeekRange } from '../utils/dateRange'

const PER_PAGE = 10
const SEARCH_DELAY_MS = 300

const records = ref<Leave[]>([])
const total = ref(0)
const lastPage = ref(1)
const page = ref(1)
const statusFilter = ref<LeaveStatus | null>(null)
const isLoading = ref(true)
const loadError = ref('')

const departments = ref<Department[]>([])
const keyword = ref('')
const departmentFilter = ref<number | null>(null)

// 日期以申請日期篩選，起迄皆可留空；預設為最近一週
const defaultRange = lastWeekRange()
const dateFrom = ref(defaultRange.from)
const dateTo = ref(defaultRange.to)

// 連續操作時舊請求可能比新請求晚回來，只採用最後一次發出的結果
let requestId = 0

const load = async () => {
  const currentId = ++requestId
  isLoading.value = true
  loadError.value = ''

  try {
    const result = await api.leave.records({
      search: keyword.value,
      departmentId: departmentFilter.value,
      dateFrom: dateFrom.value || null,
      dateTo: dateTo.value || null,
      status: statusFilter.value,
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

watch([statusFilter, departmentFilter, dateFrom, dateTo], () => {
  page.value = 1
  load()
})

onBeforeUnmount(() => clearTimeout(searchTimer))

const goToPage = (target: number) => {
  page.value = target
  load()
}

const statusClass = (status: LeaveStatus) =>
  status === '核准' ? 'status--approved' : status === '駁回' ? 'status--rejected' : 'status--pending'
</script>

<template>
  <section>
    <h1 class="title">精靈請假紀錄</h1>

    <RecordFilters
      v-model:keyword="keyword"
      v-model:department-id="departmentFilter"
      v-model:date-from="dateFrom"
      v-model:date-to="dateTo"
      :departments="departments"
    >
      <label class="filter">
        <span class="filter__label">審核結果</span>
        <select v-model="statusFilter" class="field__input">
          <option :value="null">全部</option>
          <option value="審核中">審核中</option>
          <option value="核准">核准</option>
          <option value="駁回">駁回</option>
        </select>
      </label>
    </RecordFilters>

    <p class="hint">日期為申請日期。</p>
    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col">精靈編號</th>
            <th scope="col">申請日期</th>
            <th scope="col">假別</th>
            <th scope="col">審核日期</th>
            <th scope="col">審核結果</th>
            <th scope="col">駁回理由</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="record in records" :key="record.id">
            <td>{{ record.elfNumber }}　{{ record.elfName }}</td>
            <td>{{ record.appliedAt }}</td>
            <td>{{ record.leaveType }}</td>
            <td>{{ record.reviewedAt ?? '' }}</td>
            <td>
              <span class="status" :class="statusClass(record.status)">{{ record.status }}</span>
            </td>
            <td class="table__note">{{ record.rejectReason ?? '' }}</td>
          </tr>
          <tr v-if="!isLoading && records.length === 0">
            <td colspan="6" class="table-empty">沒有符合條件的請假紀錄</td>
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

.hint {
  margin-top: 0.75rem;
  font-size: 0.875rem;
  color: var(--color-text-muted);
}
</style>
