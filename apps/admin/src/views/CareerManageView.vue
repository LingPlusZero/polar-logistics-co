<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Career, Department } from '@shared/api'
import { onMounted, ref } from 'vue'
import CareerFormDialog from '../components/CareerFormDialog.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'

const careers = ref<Career[]>([])
const departments = ref<Department[]>([])
const isLoading = ref(true)
const loadError = ref('')
const notice = ref('')

const load = async () => {
  isLoading.value = true
  loadError.value = ''

  try {
    careers.value = await api.career.list()
  } catch (error) {
    loadError.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  departments.value = await api.department.list().catch(() => [])
  await load()
})

// 新增／修改視窗
const isFormOpen = ref(false)
const editingCareer = ref<Career | null>(null)

const openCreate = () => {
  editingCareer.value = null
  isFormOpen.value = true
}

const openEdit = (career: Career) => {
  editingCareer.value = career
  isFormOpen.value = true
}

const onSaved = async (saved: Career) => {
  notice.value = `${editingCareer.value ? '已更新' : '已新增'}職缺「${saved.title}」`
  isFormOpen.value = false
  await load()
}

// 刪除二次確認
const deletingCareer = ref<Career | null>(null)
const isDeleting = ref(false)
const deleteError = ref('')

const askDelete = (career: Career) => {
  deleteError.value = ''
  deletingCareer.value = career
}

const confirmDelete = async () => {
  const target = deletingCareer.value

  if (!target) {
    return
  }

  isDeleting.value = true
  deleteError.value = ''

  try {
    await api.career.remove(target.id)
    notice.value = `已刪除職缺「${target.title}」`
    deletingCareer.value = null
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
    <header class="career__header">
      <h1 class="career__title">職缺管理</h1>
      <button type="button" class="button button--primary" @click="openCreate">新增職缺</button>
    </header>
    <p class="career__hint">這裡的職缺會顯示在官網「加入我們」。</p>

    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <p v-if="loadError" class="field__error" role="alert">{{ loadError }}</p>

    <div class="table-wrap" :aria-busy="isLoading">
      <table class="table" :class="{ 'table--loading': isLoading }">
        <thead>
          <tr>
            <th scope="col">職缺名稱</th>
            <th scope="col">職缺部門</th>
            <th scope="col">工作內容</th>
            <th scope="col"><span class="visually-hidden">操作</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="career in careers" :key="career.id">
            <th scope="row" class="table__name">{{ career.title }}</th>
            <td>{{ career.department ?? '不限部門' }}</td>
            <!-- 工作內容在列表只顯示前幾行，完整內容在修改視窗 -->
            <td class="table__note"><p class="clamp">{{ career.description }}</p></td>
            <td>
              <div class="table__actions">
                <button type="button" class="link" @click="openEdit(career)">修改</button>
                <button type="button" class="link link--danger" @click="askDelete(career)">刪除</button>
              </div>
            </td>
          </tr>
          <tr v-if="!isLoading && careers.length === 0">
            <td colspan="4" class="table-empty">還沒有職缺</td>
          </tr>
        </tbody>
      </table>
    </div>

    <CareerFormDialog
      :open="isFormOpen"
      :career="editingCareer"
      :departments="departments"
      @close="isFormOpen = false"
      @saved="onSaved"
    />

    <ConfirmDialog
      :open="deletingCareer !== null"
      title="刪除職缺"
      :message="`確定要刪除職缺「${deletingCareer?.title}」嗎？\n刪除後官網不再顯示，且無法復原。`"
      confirm-label="確定刪除"
      :is-busy="isDeleting"
      :error-message="deleteError"
      @confirm="confirmDelete"
      @cancel="deletingCareer = null"
    />
  </section>
</template>

<style scoped>
.career__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.career__title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.career__hint {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

/* 條列內容以換行分隔：保留換行並限制列表高度，避免一個職缺撐出很長的一列 */
.clamp {
  display: -webkit-box;
  min-width: 12rem;
  overflow: hidden;
  white-space: pre-line;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  line-clamp: 3;
}
</style>
