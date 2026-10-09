<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Complaint, ComplaintStatus } from '@shared/api'
import { onMounted, ref, watch } from 'vue'
import ModalDialog from '../components/ModalDialog.vue'
import PaginationBar from '../components/PaginationBar.vue'
import { useAuth } from '../composables/useAuth'

const PER_PAGE = 10

const { profile } = useAuth()

const complaints = ref<Complaint[]>([])
const total = ref(0)
const lastPage = ref(1)
const page = ref(1)
const statusFilter = ref<ComplaintStatus | null>(null)
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
    const result = await api.complaint.list({
      status: statusFilter.value,
      page: page.value,
      perPage: PER_PAGE,
    })

    if (currentId !== requestId) {
      return
    }

    complaints.value = result.items
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

watch(statusFilter, () => {
  page.value = 1
  load()
})

const goToPage = (target: number) => {
  page.value = target
  load()
}

// 結案視窗：後續處理說明必填，處理人由系統帶入登入者
const closingComplaint = ref<Complaint | null>(null)
const resolution = ref('')
const resolutionError = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

const openClose = (complaint: Complaint) => {
  closingComplaint.value = complaint
  resolution.value = ''
  resolutionError.value = ''
  errorMessage.value = ''
}

const submitClose = async () => {
  const target = closingComplaint.value

  if (!target) {
    return
  }

  resolutionError.value = ''
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await api.complaint.close(target.id, resolution.value.trim())
    notice.value = `已結案：${target.elfName}（${target.elfNumber}）的申訴`
    closingComplaint.value = null
    await load()
  } catch (error) {
    if (error instanceof ApiError && error.errors.resolution) {
      resolutionError.value = error.errors.resolution[0]
    } else {
      errorMessage.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'

      // 已被別人結案時，重新整理清單讓畫面與實際一致
      if (error instanceof ApiError && error.status === 409) {
        await load()
      }
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section>
    <h1 class="title">精靈被申訴紀錄</h1>

    <form class="filters" role="search" @submit.prevent>
      <label class="filter">
        <span class="filter__label">狀態</span>
        <select v-model="statusFilter" class="field__input">
          <option :value="null">全部</option>
          <option value="處理中">處理中</option>
          <option value="已結案">已結案</option>
        </select>
      </label>
    </form>

    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col">精靈編號</th>
            <th scope="col">申訴日期</th>
            <th scope="col">申訴事由</th>
            <th scope="col">狀態</th>
            <th scope="col">後續處理</th>
            <th scope="col">處理人</th>
            <th scope="col"><span class="visually-hidden">操作</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="complaint in complaints" :key="complaint.id">
            <td>{{ complaint.elfNumber }}　{{ complaint.elfName }}</td>
            <td>{{ complaint.filedAt }}</td>
            <td class="table__note reason">{{ complaint.reason }}</td>
            <td>
              <span
                class="status"
                :class="complaint.status === '處理中' ? 'status--processing' : 'status--closed'"
              >
                {{ complaint.status }}
              </span>
            </td>
            <td class="table__note">{{ complaint.resolution ?? '' }}</td>
            <td>{{ complaint.handler ?? '' }}</td>
            <td class="table__actions">
              <!-- 已結案不能再更改 -->
              <button
                v-if="complaint.status === '處理中'"
                type="button"
                class="link"
                @click="openClose(complaint)"
              >
                結案
              </button>
            </td>
          </tr>
          <tr v-if="!isLoading && complaints.length === 0">
            <td colspan="7" class="table-empty">沒有符合條件的申訴紀錄</td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar :page="page" :last-page="lastPage" :total="total" @change="goToPage" />

    <ModalDialog :open="closingComplaint !== null" title="申訴結案" @close="closingComplaint = null">
      <form id="close-form" novalidate @submit.prevent="submitClose">
        <p class="close__target">
          被申訴人：{{ closingComplaint?.elfName }}（{{ closingComplaint?.elfNumber }}）<br />
          申訴事由：{{ closingComplaint?.reason }}
        </p>

        <label class="field">
          <span class="field__label">後續處理說明（必填）</span>
          <textarea
            v-model="resolution"
            class="field__input"
            rows="4"
            maxlength="2000"
            required
          ></textarea>
          <span class="field__hint">處理人由系統帶入：{{ profile?.name }}。結案後無法再更改。</span>
          <span v-if="resolutionError" class="field__error" role="alert">{{ resolutionError }}</span>
        </label>

        <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>
      </form>

      <template #footer>
        <button type="button" class="button" :disabled="isSubmitting" @click="closingComplaint = null">
          取消
        </button>
        <button
          type="submit"
          form="close-form"
          class="button button--primary"
          :disabled="isSubmitting || resolution.trim() === ''"
        >
          確定結案
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

.filters {
  display: grid;
  gap: 0.75rem 1rem;
  margin-top: 1.25rem;
  max-width: 16rem;
}

.filter__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

/* 申訴事由是主要內容，不用淡化成備註色 */
.reason {
  color: inherit;
}

.status--processing {
  font-weight: 700;
  color: var(--color-red);
}

.status--closed {
  color: var(--color-text-muted);
  background: var(--color-ice);
}

.close__target {
  margin-bottom: 1rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  background: var(--color-ice);
}
</style>
