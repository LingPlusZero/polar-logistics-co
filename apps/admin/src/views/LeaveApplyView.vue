<script setup lang="ts">
import {
  ApiError,
  LEAVE_TYPES,
  PEAK_SEASON_MESSAGE,
  api,
  leaveEndDate,
  touchesPeakSeason,
} from '@shared/api'
import type { Leave, LeaveStatus, LeaveType } from '@shared/api'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import PaginationBar from '../components/PaginationBar.vue'

const PER_PAGE = 10

// 本地日期 YYYY-MM-DD；起日不可早於今天
const today = new Date().toLocaleDateString('sv-SE')
const isPeakSeasonNow = new Date().getMonth() === 11

const form = reactive<{ leaveType: LeaveType; startDate: string }>({
  leaveType: LEAVE_TYPES[0].type,
  startDate: '',
})

const fieldErrors = ref<Record<string, string>>({})
const errorMessage = ref('')
const notice = ref('')
const isSubmitting = ref(false)

const selectedDays = computed(() => LEAVE_TYPES.find((item) => item.type === form.leaveType)?.days ?? 1)
const endDate = computed(() => (form.startDate ? leaveEndDate(form.startDate, form.leaveType) : ''))

// 選好日期就先提醒旺季不能請假，不用等送出才知道（後端仍會再驗證）
const peakWarning = computed(() =>
  form.startDate && touchesPeakSeason(form.startDate, endDate.value) ? PEAK_SEASON_MESSAGE : '',
)

const canSubmit = computed(
  () => !isSubmitting.value && !isPeakSeasonNow && form.startDate !== '' && peakWarning.value === '',
)

const leaves = ref<Leave[]>([])
const total = ref(0)
const lastPage = ref(1)
const page = ref(1)
const statusFilter = ref<LeaveStatus | null>(null)
// 日期以申請日期篩選，起迄皆可留空（預設看全部）
const dateFrom = ref('')
const dateTo = ref('')
const isLoading = ref(true)
const loadError = ref('')

// 連續操作時舊請求可能比新請求晚回來，只採用最後一次發出的結果
let requestId = 0

const load = async () => {
  const currentId = ++requestId
  isLoading.value = true
  loadError.value = ''

  try {
    const result = await api.leave.mine({
      status: statusFilter.value,
      dateFrom: dateFrom.value || null,
      dateTo: dateTo.value || null,
      page: page.value,
      perPage: PER_PAGE,
    })

    if (currentId !== requestId) {
      return
    }

    leaves.value = result.items
    total.value = result.total
    lastPage.value = result.lastPage
    page.value = result.page

    // 後端不會修正超出範圍的頁數，回到最後一頁重取
    if (result.items.length === 0 && result.total > 0 && result.page > result.lastPage) {
      page.value = result.lastPage
      await load()
    }
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

onMounted(load)

watch([statusFilter, dateFrom, dateTo], () => {
  page.value = 1
  load()
})

const clearDates = () => {
  dateFrom.value = ''
  dateTo.value = ''
}

const goToPage = (target: number) => {
  page.value = target
  load()
}

const submit = async () => {
  fieldErrors.value = {}
  errorMessage.value = ''
  notice.value = ''
  isSubmitting.value = true

  try {
    const leave = await api.leave.apply({ leaveType: form.leaveType, startDate: form.startDate })

    notice.value =
      leave.status === '核准'
        ? `已送出並核准：${leave.leaveType}，${leave.startDate} 至 ${leave.endDate}。`
        : `已送出：${leave.leaveType}，${leave.startDate} 至 ${leave.endDate}，等待主管審核。`
    form.startDate = ''
    page.value = 1
    await load()
  } catch (error) {
    if (error instanceof ApiError && Object.keys(error.errors).length > 0) {
      fieldErrors.value = Object.fromEntries(
        Object.entries(error.errors).map(([field, messages]) => [field, messages[0]]),
      )
    } else {
      errorMessage.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
    }
  } finally {
    isSubmitting.value = false
  }
}

const statusClass = (status: LeaveStatus) =>
  status === '核准' ? 'status--approved' : status === '駁回' ? 'status--rejected' : 'status--pending'
</script>

<template>
  <section>
    <h1 class="title">請假申請</h1>

    <!-- 旺季規則平時就顯示，12 月時表單會停用 -->
    <p class="peak" role="note">{{ PEAK_SEASON_MESSAGE }}</p>
    <p v-if="notice" class="notice apply__notice" role="status">{{ notice }}</p>

    <form class="apply__form" novalidate @submit.prevent="submit">
      <label class="field apply__field">
        <span class="field__label">假別</span>
        <select v-model="form.leaveType" class="field__input" :disabled="isPeakSeasonNow">
          <option v-for="item in LEAVE_TYPES" :key="item.type" :value="item.type">
            {{ item.type }}（{{ item.days }} 天）
          </option>
        </select>
      </label>

      <label class="field apply__field">
        <span class="field__label">請假起日</span>
        <input
          v-model="form.startDate"
          class="field__input"
          type="date"
          :min="today"
          :disabled="isPeakSeasonNow"
          required
        />
      </label>

      <button class="button button--primary apply__submit" type="submit" :disabled="!canSubmit">
        送出申請
      </button>

      <!-- 欄位並排後，提示與錯誤統一放在整列下方，避免把按鈕擠歪 -->
      <div class="apply__messages">
        <p v-if="endDate && !peakWarning" class="field__hint">
          共 {{ selectedDays }} 天，至 {{ endDate }} 止（含當天）。每張假單不可與既有假單重疊，送出後由主管審核。
        </p>
        <p v-else-if="!endDate && !peakWarning && !fieldErrors.startDate" class="field__hint">
          每張假單不可與既有假單重疊，送出後由主管審核。
        </p>
        <p v-if="peakWarning || fieldErrors.startDate" class="field__error" role="alert">
          {{ peakWarning || fieldErrors.startDate }}
        </p>
        <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>
      </div>
    </form>

    <h2 class="apply__subtitle">我的請假單</h2>

    <form class="apply__filters" role="search" @submit.prevent>
      <label class="filter">
        <span class="filter__label">審核結果</span>
        <select v-model="statusFilter" class="field__input">
          <option :value="null">全部</option>
          <option value="審核中">審核中</option>
          <option value="核准">核准</option>
          <option value="駁回">駁回</option>
        </select>
      </label>

      <label class="filter">
        <span class="filter__label">申請日期（起）</span>
        <input v-model="dateFrom" class="field__input" type="date" :max="dateTo || undefined" />
      </label>

      <label class="filter">
        <span class="filter__label">申請日期（迄）</span>
        <input v-model="dateTo" class="field__input" type="date" :min="dateFrom || undefined" />
      </label>

      <button
        type="button"
        class="button apply__clear"
        :disabled="!dateFrom && !dateTo"
        @click="clearDates"
      >
        清除日期
      </button>
    </form>

    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col">假別</th>
            <th scope="col">請假期間</th>
            <th scope="col">申請日期</th>
            <th scope="col">審核結果</th>
            <th scope="col">審核日期</th>
            <th scope="col">審核人</th>
            <th scope="col">駁回理由</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="leave in leaves" :key="leave.id">
            <td>{{ leave.leaveType }}</td>
            <td>
              {{ leave.startDate === leave.endDate ? leave.startDate : `${leave.startDate} ～ ${leave.endDate}` }}（{{
                leave.days
              }}
              天）
            </td>
            <td>{{ leave.appliedAt }}</td>
            <td>
              <span class="status" :class="statusClass(leave.status)">{{ leave.status }}</span>
            </td>
            <td>{{ leave.reviewedAt ?? '' }}</td>
            <td>{{ leave.reviewer ?? '' }}</td>
            <td class="table__note">{{ leave.rejectReason ?? '' }}</td>
          </tr>
          <tr v-if="!isLoading && leaves.length === 0">
            <td colspan="7" class="table-empty">還沒有請假單</td>
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

.peak {
  max-width: 32rem;
  margin-top: 1rem;
  padding: 0.5rem 0.75rem;
  font-weight: 700;
  color: var(--color-red);
  border-left: 3px solid var(--color-red);
}

.apply__notice {
  max-width: 48rem;
}

/* 欄位並排，提示與錯誤放在整列下方；窄螢幕改為直向堆疊 */
.apply__form {
  display: grid;
  gap: 0.75rem 1rem;
  align-items: end;
  margin-top: 1.5rem;
}

/* 並排時間距由 grid gap 控制，不需要欄位自己的下邊距 */
.apply__field {
  margin-bottom: 0;
}

/* 窄螢幕時 grid 會把按鈕拉成整列寬，維持內容寬度；高度與旁邊的輸入框對齊 */
.apply__submit {
  justify-self: start;
  padding: 0.625rem 1.25rem;
}

.apply__messages {
  grid-column: 1 / -1;
}

.apply__subtitle {
  margin-top: 2.5rem;
  font-size: 1.125rem;
  line-height: 1.4;
}

.apply__filters {
  display: grid;
  gap: 0.75rem 1rem;
  align-items: end;
  margin: 1rem 0;
}

.apply__clear {
  justify-self: start;
  padding: 0.625rem 1.25rem;
}

@media (min-width: 48em) {
  .apply__form {
    grid-template-columns: 18rem 14rem auto;
  }

  .apply__filters {
    grid-template-columns: repeat(3, 12rem) auto;
  }
}
</style>
