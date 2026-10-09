<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Leave, LeaveStatus } from '@shared/api'
import { onMounted, ref, watch } from 'vue'
import ModalDialog from '../components/ModalDialog.vue'
import PaginationBar from '../components/PaginationBar.vue'
import { useAuth } from '../composables/useAuth'

const PER_PAGE = 10

const { profile } = useAuth()

const leaves = ref<Leave[]>([])
const total = ref(0)
const lastPage = ref(1)
const page = ref(1)
// 審核頁預設只看待審核的
const statusFilter = ref<LeaveStatus | null>('審核中')
const isLoading = ref(true)
const loadError = ref('')
const notice = ref('')

// 連續操作時舊請求可能比新請求晚回來，只採用最後一次發出的結果
let requestId = 0

const load = async () => {
  const currentId = ++requestId
  isLoading.value = true
  loadError.value = ''

  try {
    const result = await api.leave.review({ status: statusFilter.value, page: page.value, perPage: PER_PAGE })

    if (currentId !== requestId) {
      return
    }

    leaves.value = result.items
    total.value = result.total
    lastPage.value = result.lastPage
    page.value = result.page

    // 審核完最後一頁的最後一筆後，後端不會修正頁數，回到最後一頁重取
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

watch(statusFilter, () => {
  page.value = 1
  load()
})

const goToPage = (target: number) => {
  page.value = target
  load()
}

const describe = (leave: Leave) => `${leave.elfName}（${leave.elfNumber}）的${leave.leaveType}`

// 核准不需要說明，直接送出；操作中的那一列停用按鈕，避免連點
const actingId = ref<number | null>(null)

const approve = async (leave: Leave) => {
  actingId.value = leave.id
  notice.value = ''
  loadError.value = ''

  try {
    await api.leave.approve(leave.id)
    notice.value = `已核准：${describe(leave)}`
  } catch (error) {
    loadError.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
  } finally {
    actingId.value = null
    // 成功或遇到 409（已被別人審核）都重新整理，讓畫面與實際一致
    await load()
  }
}

// 駁回視窗：駁回理由必填
const rejectingLeave = ref<Leave | null>(null)
const reason = ref('')
const reasonError = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

const openReject = (leave: Leave) => {
  rejectingLeave.value = leave
  reason.value = ''
  reasonError.value = ''
  errorMessage.value = ''
}

const submitReject = async () => {
  const target = rejectingLeave.value

  if (!target) {
    return
  }

  reasonError.value = ''
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await api.leave.reject(target.id, reason.value.trim())
    notice.value = `已駁回：${describe(target)}`
    rejectingLeave.value = null
    await load()
  } catch (error) {
    if (error instanceof ApiError && error.errors.reason) {
      reasonError.value = error.errors.reason[0]
    } else {
      errorMessage.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'

      if (error instanceof ApiError && error.status === 409) {
        await load()
      }
    }
  } finally {
    isSubmitting.value = false
  }
}

const leaveSubject = (leave: Leave) =>
  leave.reindeerName ? `馴鹿 ${leave.reindeerName}（${leave.reindeerNumber}）` : '自己'

const statusClass = (status: LeaveStatus) =>
  status === '核准' ? 'status--approved' : status === '駁回' ? 'status--rejected' : 'status--pending'
</script>

<template>
  <section>
    <h1 class="title">請假審核</h1>
    <p class="scope">
      {{
        profile?.rank === '副聖誕老人'
          ? '審核範圍：各部長的假單與自己的假單'
          : `審核範圍：${profile?.department ?? ''}的精靈假單（含實習生）`
      }}
    </p>

    <div class="review__filter">
      <label class="filter">
        <span class="filter__label">審核結果</span>
        <select v-model="statusFilter" class="field__input">
          <option :value="null">全部</option>
          <option value="審核中">審核中</option>
          <option value="核准">核准</option>
          <option value="駁回">駁回</option>
        </select>
      </label>
    </div>

    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col">申請人</th>
            <th scope="col">請假對象</th>
            <th scope="col">假別</th>
            <th scope="col">請假期間</th>
            <th scope="col">申請日期</th>
            <th scope="col">審核結果</th>
            <th scope="col">審核日期</th>
            <th scope="col">駁回理由</th>
            <th scope="col"><span class="visually-hidden">操作</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="leave in leaves" :key="leave.id">
            <td>{{ leave.elfNumber }}　{{ leave.elfName }}</td>
            <td>{{ leaveSubject(leave) }}</td>
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
            <td class="table__note">{{ leave.rejectReason ?? '' }}</td>
            <td>
              <div class="table__actions">
                <!-- 已審核的假單不能再更改 -->
                <template v-if="leave.status === '審核中'">
                  <button type="button" class="link" :disabled="actingId === leave.id" @click="approve(leave)">
                    核准
                  </button>
                  <button
                    type="button"
                    class="link link--danger"
                    :disabled="actingId === leave.id"
                    @click="openReject(leave)"
                  >
                    駁回
                  </button>
                </template>
              </div>
            </td>
          </tr>
          <tr v-if="!isLoading && leaves.length === 0">
            <td colspan="9" class="table-empty">沒有符合條件的假單</td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar :page="page" :last-page="lastPage" :total="total" @change="goToPage" />

    <ModalDialog :open="rejectingLeave !== null" title="駁回假單" @close="rejectingLeave = null">
      <form id="reject-form" novalidate @submit.prevent="submitReject">
        <p v-if="rejectingLeave" class="reject__target">
          申請人：{{ rejectingLeave.elfName }}（{{ rejectingLeave.elfNumber }}）<br />
          {{ rejectingLeave.leaveType }}：{{ rejectingLeave.startDate }} ～ {{ rejectingLeave.endDate }}
        </p>

        <label class="field">
          <span class="field__label">駁回理由（必填）</span>
          <textarea v-model="reason" class="field__input" rows="4" maxlength="500" required></textarea>
          <span class="field__hint">{{ reason.length }} / 500。駁回後無法再更改。</span>
          <span v-if="reasonError" class="field__error" role="alert">{{ reasonError }}</span>
        </label>

        <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>
      </form>

      <template #footer>
        <button type="button" class="button" :disabled="isSubmitting" @click="rejectingLeave = null">
          取消
        </button>
        <button
          type="submit"
          form="reject-form"
          class="button button--danger"
          :disabled="isSubmitting || reason.trim() === ''"
        >
          確定駁回
        </button>
      </template>
    </ModalDialog>
  </section>
</template>

<style scoped>
.title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.scope {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.review__filter {
  max-width: 12rem;
  margin: 1rem 0;
}

.reject__target {
  margin-bottom: 1rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  background: var(--color-ice);
}
</style>
