<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Department, Elf, ElfSortKey, SortOrder } from '@shared/api'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ElfFormDialog from '../components/ElfFormDialog.vue'
import PaginationBar from '../components/PaginationBar.vue'
import { formatSeniority } from '../utils/seniority'

const PER_PAGE = 10
const SEARCH_DELAY_MS = 300

const elves = ref<Elf[]>([])
const departments = ref<Department[]>([])
const total = ref(0)
const lastPage = ref(1)
const isLoading = ref(true)
const loadError = ref('')

const keyword = ref('')
const departmentFilter = ref<number | null>(null)
const sortKey = ref<ElfSortKey>('number')
const sortOrder = ref<SortOrder>('asc')
const page = ref(1)

// 連續操作時舊請求可能比新請求晚回來，只採用最後一次發出的結果
let requestId = 0

const load = async () => {
  const currentId = ++requestId
  isLoading.value = true
  loadError.value = ''

  try {
    const result = await api.elf.list({
      search: keyword.value,
      departmentId: departmentFilter.value,
      sort: sortKey.value,
      order: sortOrder.value,
      page: page.value,
      perPage: PER_PAGE,
    })

    if (currentId !== requestId) {
      return
    }

    elves.value = result.items
    total.value = result.total
    lastPage.value = result.lastPage
    page.value = result.page

    // 後端不會修正超出範圍的頁數（例如刪光最後一頁），回到最後一頁重取
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

onMounted(async () => {
  departments.value = await api.department.list().catch(() => [])
  await load()
})

// 篩選或排序改變就回到第一頁；搜尋文字等使用者停手再送出
let searchTimer: ReturnType<typeof setTimeout> | undefined

watch(keyword, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    load()
  }, SEARCH_DELAY_MS)
})

watch(departmentFilter, () => {
  page.value = 1
  load()
})

onBeforeUnmount(() => clearTimeout(searchTimer))

// 點同一欄切換升冪／降冪；換欄時年資先看高的，其他先升冪
const toggleSort = (key: ElfSortKey) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = key === 'seniority' ? 'desc' : 'asc'
  }

  page.value = 1
  load()
}

const ariaSort = (key: ElfSortKey) =>
  sortKey.value === key ? (sortOrder.value === 'asc' ? 'ascending' : 'descending') : 'none'

const sortArrow = (key: ElfSortKey) =>
  sortKey.value === key ? (sortOrder.value === 'asc' ? '▲' : '▼') : '↕'

const goToPage = (target: number) => {
  page.value = Math.min(Math.max(1, target), lastPage.value)
  load()
}

const statusClass = (status: Elf['status']) =>
  status === '正常' ? 'status--normal' : status === '請假' ? 'status--leave' : 'status--missing'

// 新增／修改視窗
const isFormOpen = ref(false)
const editingElf = ref<Elf | null>(null)
const notice = ref('')

const openCreate = () => {
  editingElf.value = null
  isFormOpen.value = true
}

const openEdit = (elf: Elf) => {
  editingElf.value = elf
  isFormOpen.value = true
}

// 存檔後重新向後端取得目前這一頁，排序與篩選的結果才會正確
const onSaved = async (saved: Elf) => {
  notice.value = `${editingElf.value ? '已更新' : '已新增'} ${saved.name}（${saved.number}）`
  isFormOpen.value = false
  await load()
}

// 刪除二次確認
const deletingElf = ref<Elf | null>(null)
const isDeleting = ref(false)
const deleteError = ref('')

const askDelete = (elf: Elf) => {
  deleteError.value = ''
  deletingElf.value = elf
}

const confirmDelete = async () => {
  const target = deletingElf.value

  if (!target) {
    return
  }

  isDeleting.value = true
  deleteError.value = ''

  try {
    await api.elf.remove(target.id)
    notice.value = `已刪除 ${target.name}（${target.number}）`
    deletingElf.value = null
    await load()
  } catch (error) {
    deleteError.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <section class="roster">
    <header class="roster__header">
      <h1 class="roster__title">精靈名冊</h1>
      <button type="button" class="button button--primary" @click="openCreate">新增精靈</button>
    </header>

    <form class="roster__filters" role="search" @submit.prevent>
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
    </form>

    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col">狀態</th>
            <th scope="col">姓名</th>
            <th scope="col" :aria-sort="ariaSort('department')">
              <button type="button" class="sort" @click="toggleSort('department')">
                部門 <span aria-hidden="true">{{ sortArrow('department') }}</span>
              </button>
            </th>
            <th scope="col">職稱</th>
            <th scope="col" :aria-sort="ariaSort('number')">
              <button type="button" class="sort" @click="toggleSort('number')">
                精靈編號 <span aria-hidden="true">{{ sortArrow('number') }}</span>
              </button>
            </th>
            <th scope="col">到職日期</th>
            <th scope="col" :aria-sort="ariaSort('seniority')">
              <button type="button" class="sort" @click="toggleSort('seniority')">
                年資 <span aria-hidden="true">{{ sortArrow('seniority') }}</span>
              </button>
            </th>
            <th scope="col">備註</th>
            <th scope="col"><span class="visually-hidden">操作</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="elf in elves" :key="elf.id">
            <td>
              <span class="status" :class="statusClass(elf.status)">{{ elf.status }}</span>
            </td>
            <th scope="row" class="table__name">{{ elf.name }}</th>
            <td>{{ elf.department }}</td>
            <td>{{ elf.rank }}</td>
            <td>{{ elf.number }}</td>
            <td>{{ elf.hiredAt }}</td>
            <td>{{ formatSeniority(elf.hiredAt) }}</td>
            <td class="table__note">{{ elf.note ?? '' }}</td>
            <td>
              <div class="table__actions">
                <button type="button" class="link" @click="openEdit(elf)">修改</button>
                <button type="button" class="link link--danger" @click="askDelete(elf)">刪除</button>
              </div>
            </td>
          </tr>
          <tr v-if="!isLoading && elves.length === 0">
            <td colspan="9" class="table-empty">沒有符合條件的精靈</td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar :page="page" :last-page="lastPage" :total="total" @change="goToPage" />

    <ElfFormDialog
      :open="isFormOpen"
      :elf="editingElf"
      :departments="departments"
      @close="isFormOpen = false"
      @saved="onSaved"
    />

    <ConfirmDialog
      :open="deletingElf !== null"
      title="刪除精靈"
      :message="`確定要刪除 ${deletingElf?.name}（${deletingElf?.number}）嗎？\n刪除後無法復原。`"
      confirm-label="確定刪除"
      :is-busy="isDeleting"
      :error-message="deleteError"
      @confirm="confirmDelete"
      @cancel="deletingElf = null"
    />
  </section>
</template>

<style scoped>
.roster__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.roster__title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.roster__filters {
  display: grid;
  gap: 0.75rem 1rem;
  margin-top: 1.25rem;
}

.filter__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.status--normal {
  color: var(--color-navy);
}

.status--leave {
  color: var(--color-text-muted);
  background: var(--color-ice);
}

.status--missing {
  font-weight: 700;
  color: var(--color-red);
}

@media (min-width: 48em) {
  .roster__filters {
    grid-template-columns: 2fr 1fr;
  }
}
</style>
