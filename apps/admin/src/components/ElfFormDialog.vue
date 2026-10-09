<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Department, EditableElfStatus, Elf, ElfRank } from '@shared/api'
import { computed, reactive, ref, watch } from 'vue'
import { ELF_RANKS, ELF_STATUSES } from '../config/elf'
import { formatSeniority } from '../utils/seniority'
import ModalDialog from './ModalDialog.vue'

const props = defineProps<{
  open: boolean
  // 有帶精靈就是編輯，沒有就是新增
  elf: Elf | null
  departments: Department[]
}>()

const emit = defineEmits<{
  close: []
  saved: [elf: Elf]
}>()

const isEdit = computed(() => props.elf !== null)
const isOnLeave = computed(() => props.elf?.status === '請假')

const form = reactive({
  name: '',
  departmentId: null as number | null,
  rank: '正式精靈' as ElfRank,
  hiredAt: '',
  status: '正常' as EditableElfStatus,
  note: '',
})

const fieldErrors = ref<Record<string, string>>({})
const errorMessage = ref('')
const isSubmitting = ref(false)

const today = new Date().toLocaleDateString('sv-SE')

// 每次開啟都重填表單，避免殘留上一次的內容與錯誤
watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return
    }

    fieldErrors.value = {}
    errorMessage.value = ''
    form.name = props.elf?.name ?? ''
    form.departmentId = props.elf?.departmentId ?? null
    form.rank = props.elf?.rank ?? '正式精靈'
    form.hiredAt = props.elf?.hiredAt ?? ''
    form.status = props.elf?.status === '請假' || !props.elf ? '正常' : props.elf.status
    form.note = props.elf?.note ?? ''
  },
)

const canSubmit = computed(
  () =>
    !isSubmitting.value &&
    form.name.trim() !== '' &&
    form.departmentId !== null &&
    (isEdit.value || form.hiredAt !== ''),
)

const submit = async () => {
  if (form.departmentId === null) {
    return
  }

  fieldErrors.value = {}
  errorMessage.value = ''
  isSubmitting.value = true

  const common = {
    name: form.name.trim(),
    departmentId: form.departmentId,
    rank: form.rank,
    note: form.note.trim() === '' ? null : form.note.trim(),
  }

  try {
    const saved = props.elf
      ? await api.elf.update(props.elf.id, {
          ...common,
          // 請假中的狀態由請假單決定，不送
          ...(isOnLeave.value ? {} : { status: form.status }),
        })
      : await api.elf.create({
          ...common,
          status: form.status,
          hiredAt: form.hiredAt,
        })

    emit('saved', saved)
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
</script>

<template>
  <ModalDialog :open="open" :title="isEdit ? '修改精靈' : '新增精靈'" @close="emit('close')">
    <form id="elf-form" novalidate @submit.prevent="submit">
      <label class="field">
        <span class="field__label">精靈編號</span>
        <input
          class="field__input"
          type="text"
          :value="elf?.number ?? ''"
          :placeholder="isEdit ? '' : '儲存後自動產生'"
          disabled
        />
        <span class="field__hint">{{ isEdit ? '精靈編號不可修改' : '精靈編號由系統自動產生' }}</span>
      </label>

      <label class="field">
        <span class="field__label">姓名</span>
        <input
          v-model="form.name"
          class="field__input"
          type="text"
          maxlength="50"
          autocomplete="off"
          required
        />
        <span v-if="fieldErrors.name" class="field__error" role="alert">{{ fieldErrors.name }}</span>
      </label>

      <div class="row">
        <label class="field">
          <span class="field__label">部門</span>
          <select v-model="form.departmentId" class="field__input" required>
            <option :value="null" disabled>請選擇部門</option>
            <option v-for="department in departments" :key="department.id" :value="department.id">
              {{ department.name }}
            </option>
          </select>
          <span v-if="fieldErrors.departmentId" class="field__error" role="alert">
            {{ fieldErrors.departmentId }}
          </span>
        </label>

        <label class="field">
          <span class="field__label">職稱</span>
          <select v-model="form.rank" class="field__input" required>
            <option v-for="rank in ELF_RANKS" :key="rank" :value="rank">{{ rank }}</option>
          </select>
          <span v-if="fieldErrors.rank" class="field__error" role="alert">{{ fieldErrors.rank }}</span>
        </label>
      </div>

      <div class="row">
        <label class="field">
          <span class="field__label">到職日期</span>
          <input
            v-model="form.hiredAt"
            class="field__input"
            type="date"
            :max="today"
            :disabled="isEdit"
            required
          />
          <span v-if="isEdit" class="field__hint">到職日期與年資（{{ formatSeniority(form.hiredAt) }}）不可修改</span>
          <span v-if="fieldErrors.hiredAt" class="field__error" role="alert">{{ fieldErrors.hiredAt }}</span>
        </label>

        <label class="field">
          <span class="field__label">狀態</span>
          <input v-if="isOnLeave" class="field__input" type="text" value="請假" disabled />
          <select v-else v-model="form.status" class="field__input" required>
            <option v-for="status in ELF_STATUSES" :key="status" :value="status">{{ status }}</option>
          </select>
          <span v-if="isOnLeave" class="field__hint">請假中，狀態由請假單決定</span>
          <span v-if="fieldErrors.status" class="field__error" role="alert">{{ fieldErrors.status }}</span>
        </label>
      </div>

      <label class="field">
        <span class="field__label">備註</span>
        <textarea v-model="form.note" class="field__input" rows="3" maxlength="2000"></textarea>
        <span v-if="fieldErrors.note" class="field__error" role="alert">{{ fieldErrors.note }}</span>
      </label>

      <p v-if="!isEdit" class="field__hint">新增後以預設密碼登入，請本人盡快修改密碼。</p>
      <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>
    </form>

    <template #footer>
      <button type="button" class="button" :disabled="isSubmitting" @click="emit('close')">取消</button>
      <button type="submit" form="elf-form" class="button button--primary" :disabled="!canSubmit">
        {{ isEdit ? '儲存' : '新增' }}
      </button>
    </template>
  </ModalDialog>
</template>

<style scoped>
.row {
  display: grid;
  gap: 0 1rem;
}

@media (min-width: 30em) {
  .row {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
