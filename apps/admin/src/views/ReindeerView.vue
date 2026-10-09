<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Leave, LeaveStatus, PersonOption, Reindeer, ReindeerSortKey, SortOrder } from '@shared/api'
import { onMounted, ref } from 'vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ReindeerFormDialog from '../components/ReindeerFormDialog.vue'
import { formatSeniority } from '../utils/seniority'

const reindeerList = ref<Reindeer[]>([])
const caretakers = ref<PersonOption[]>([])
const isLoading = ref(true)
const loadError = ref('')
const notice = ref('')

// 排序在後端；預設依編號
const sortKey = ref<ReindeerSortKey>('number')
const sortOrder = ref<SortOrder>('asc')

// 連續操作時舊請求可能比新請求晚回來，只採用最後一次發出的結果
let requestId = 0

const load = async () => {
  const currentId = ++requestId
  isLoading.value = true
  loadError.value = ''

  try {
    const result = await api.reindeer.list({ sort: sortKey.value, order: sortOrder.value })

    if (currentId === requestId) {
      reindeerList.value = result
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

onMounted(async () => {
  caretakers.value = await api.reindeer.caretakers().catch(() => [])
  await load()
})

// 點同一欄切換升冪／降冪；換欄時年資先看高的，編號先升冪
const toggleSort = (key: ReindeerSortKey) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = key === 'seniority' ? 'desc' : 'asc'
  }

  load()
}

const ariaSort = (key: ReindeerSortKey) =>
  sortKey.value === key ? (sortOrder.value === 'asc' ? 'ascending' : 'descending') : 'none'

const sortArrow = (key: ReindeerSortKey) =>
  sortKey.value === key ? (sortOrder.value === 'asc' ? '▲' : '▼') : '↕'

// 新增／修改視窗
const isFormOpen = ref(false)
const editingReindeer = ref<Reindeer | null>(null)

const openCreate = () => {
  editingReindeer.value = null
  isFormOpen.value = true
}

const openEdit = (reindeer: Reindeer) => {
  editingReindeer.value = reindeer
  isFormOpen.value = true
}

const onSaved = async (saved: Reindeer) => {
  notice.value = `${editingReindeer.value ? '已更新' : '已新增'} ${saved.name}（${saved.number}）`
  isFormOpen.value = false
  await load()
}

// 請假紀錄：一次只展開一隻，展開時才向後端取
const expandedId = ref<number | null>(null)
const leaves = ref<Leave[]>([])
const isLeavesLoading = ref(false)
const leavesError = ref('')

const toggleLeaves = async (reindeer: Reindeer) => {
  if (expandedId.value === reindeer.id) {
    expandedId.value = null
    return
  }

  expandedId.value = reindeer.id
  leaves.value = []
  leavesError.value = ''
  isLeavesLoading.value = true

  try {
    const result = await api.reindeer.leaves(reindeer.id)

    // 載入期間若已改展開別隻，丟棄這次結果
    if (expandedId.value === reindeer.id) {
      leaves.value = result
    }
  } catch (error) {
    if (expandedId.value === reindeer.id) {
      leavesError.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
    }
  } finally {
    isLeavesLoading.value = false
  }
}

const statusClass = (status: LeaveStatus) =>
  status === '核准' ? 'status--approved' : status === '駁回' ? 'status--rejected' : 'status--pending'

// 刪除二次確認
const deletingReindeer = ref<Reindeer | null>(null)
const isDeleting = ref(false)
const deleteError = ref('')

const askDelete = (reindeer: Reindeer) => {
  deleteError.value = ''
  deletingReindeer.value = reindeer
}

const confirmDelete = async () => {
  const target = deletingReindeer.value

  if (!target) {
    return
  }

  isDeleting.value = true
  deleteError.value = ''

  try {
    await api.reindeer.remove(target.id)
    notice.value = `已刪除 ${target.name}（${target.number}）`
    deletingReindeer.value = null

    if (expandedId.value === target.id) {
      expandedId.value = null
    }

    await load()
  } catch (error) {
    deleteError.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <section>
    <header class="reindeer__header">
      <h1 class="reindeer__title">動力單位管理</h1>
      <button type="button" class="button button--primary" @click="openCreate">新增動力單位</button>
    </header>

    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col">姓名</th>
            <th scope="col" :aria-sort="ariaSort('number')">
              <button type="button" class="sort" @click="toggleSort('number')">
                編號 <span aria-hidden="true">{{ sortArrow('number') }}</span>
              </button>
            </th>
            <th scope="col">到職日期</th>
            <th scope="col" :aria-sort="ariaSort('seniority')">
              <button type="button" class="sort" @click="toggleSort('seniority')">
                年資 <span aria-hidden="true">{{ sortArrow('seniority') }}</span>
              </button>
            </th>
            <th scope="col">上次保養日期</th>
            <th scope="col">下次保養日期</th>
            <th scope="col">照護專員</th>
            <th scope="col">備註</th>
            <th scope="col"><span class="visually-hidden">操作</span></th>
          </tr>
        </thead>
        <tbody>
          <template v-for="reindeer in reindeerList" :key="reindeer.id">
            <tr>
              <th scope="row" class="table__name">{{ reindeer.name }}</th>
              <td>{{ reindeer.number }}</td>
              <td>{{ reindeer.hiredAt }}</td>
              <td>{{ formatSeniority(reindeer.hiredAt) }}</td>
              <td>{{ reindeer.lastMaintainedAt }}</td>
              <td>{{ reindeer.nextMaintenanceAt }}</td>
              <td>{{ reindeer.caretaker ?? '—' }}</td>
              <td class="table__note">{{ reindeer.note ?? '' }}</td>
              <td>
                <div class="table__actions">
                  <button
                    type="button"
                    class="link"
                    :aria-expanded="expandedId === reindeer.id"
                    :aria-controls="`leaves-${reindeer.id}`"
                    @click="toggleLeaves(reindeer)"
                  >
                    {{ expandedId === reindeer.id ? '收合請假記錄' : '請假記錄' }}
                  </button>
                  <button type="button" class="link" @click="openEdit(reindeer)">修改</button>
                  <button type="button" class="link link--danger" @click="askDelete(reindeer)">
                    刪除
                  </button>
                </div>
              </td>
            </tr>

            <!-- 子表格：該馴鹿的請假記錄 -->
            <tr v-if="expandedId === reindeer.id" :id="`leaves-${reindeer.id}`" class="leaves">
              <td colspan="9">
                <p v-if="leavesError" class="field__error" role="alert">{{ leavesError }}</p>
                <p v-else-if="isLeavesLoading" class="leaves__empty">載入中…</p>
                <p v-else-if="leaves.length === 0" class="leaves__empty">沒有請假記錄</p>
                <table v-else class="table leaves__table">
                  <thead>
                    <tr>
                      <th scope="col">申請日期</th>
                      <th scope="col">假別</th>
                      <th scope="col">審核日期</th>
                      <th scope="col">審核結果</th>
                      <th scope="col">駁回理由</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="leave in leaves" :key="leave.id">
                      <td>{{ leave.appliedAt }}</td>
                      <td>{{ leave.leaveType }}</td>
                      <td>{{ leave.reviewedAt ?? '' }}</td>
                      <td>
                        <span class="status" :class="statusClass(leave.status)">{{ leave.status }}</span>
                      </td>
                      <td class="table__note">{{ leave.rejectReason ?? '' }}</td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>
          <tr v-if="!isLoading && reindeerList.length === 0">
            <td colspan="9" class="table-empty">還沒有動力單位</td>
          </tr>
        </tbody>
      </table>
    </div>

    <ReindeerFormDialog
      :open="isFormOpen"
      :reindeer="editingReindeer"
      :caretakers="caretakers"
      @close="isFormOpen = false"
      @saved="onSaved"
    />

    <ConfirmDialog
      :open="deletingReindeer !== null"
      title="刪除動力單位"
      :message="`確定要刪除 ${deletingReindeer?.name}（${deletingReindeer?.number}）嗎？\n該動力單位的請假記錄會一併刪除，刪除後無法復原。`"
      confirm-label="確定刪除"
      :is-busy="isDeleting"
      :error-message="deleteError"
      @confirm="confirmDelete"
      @cancel="deletingReindeer = null"
    />
  </section>
</template>

<style scoped>
.reindeer__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.reindeer__title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.leaves > td {
  background: var(--color-ice);
}

.leaves__empty {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

/* 子表格縮排並用白底，與外層表格區隔 */
.leaves__table {
  background: #fff;
}
</style>
