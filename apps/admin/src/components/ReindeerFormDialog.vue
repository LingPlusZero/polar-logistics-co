<script setup lang="ts">
import { ApiError, MAINTENANCE_INTERVAL_MONTHS, api, nextMaintenanceDate } from '@shared/api'
import type { PersonOption, Reindeer } from '@shared/api'
import { computed, reactive, ref, watch } from 'vue'
import { formatSeniority } from '../utils/seniority'
import ModalDialog from './ModalDialog.vue'

const props = defineProps<{
  open: boolean
  // 有帶動力單位就是編輯，沒有就是新增
  reindeer: Reindeer | null
  caretakers: PersonOption[]
}>()

const emit = defineEmits<{
  close: []
  saved: [reindeer: Reindeer]
}>()

const isEdit = computed(() => props.reindeer !== null)

const form = reactive({
  name: '',
  hiredAt: '',
  lastMaintainedAt: '',
  caretakerId: null as number | null,
  note: '',
})

const fieldErrors = ref<Record<string, string>>({})
const errorMessage = ref('')
const isSubmitting = ref(false)

const today = new Date().toLocaleDateString('sv-SE')

// 下次保養日期不可修改，隨上次保養日期即時算出供確認
const nextMaintenance = computed(() =>
  form.lastMaintainedAt ? nextMaintenanceDate(form.lastMaintainedAt) : '',
)

// 每次開啟都重填表單，避免殘留上一次的內容與錯誤
watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return
    }

    fieldErrors.value = {}
    errorMessage.value = ''
    form.name = props.reindeer?.name ?? ''
    form.hiredAt = props.reindeer?.hiredAt ?? ''
    form.lastMaintainedAt = props.reindeer?.lastMaintainedAt ?? ''
    form.caretakerId = props.reindeer?.caretakerId ?? null
    form.note = props.reindeer?.note ?? ''
  },
)

const canSubmit = computed(
  () =>
    !isSubmitting.value &&
    form.name.trim() !== '' &&
    form.lastMaintainedAt !== '' &&
    form.caretakerId !== null &&
    (isEdit.value || form.hiredAt !== ''),
)

const submit = async () => {
  if (form.caretakerId === null) {
    return
  }

  fieldErrors.value = {}
  errorMessage.value = ''
  isSubmitting.value = true

  const common = {
    name: form.name.trim(),
    lastMaintainedAt: form.lastMaintainedAt,
    caretakerId: form.caretakerId,
    note: form.note.trim() === '' ? null : form.note.trim(),
  }

  try {
    const saved = props.reindeer
      ? await api.reindeer.update(props.reindeer.id, common)
      : await api.reindeer.create({ ...common, hiredAt: form.hiredAt })

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
  <ModalDialog :open="open" :title="isEdit ? '修改動力單位' : '新增動力單位'" @close="emit('close')">
    <form id="reindeer-form" novalidate @submit.prevent="submit">
      <label class="field">
        <span class="field__label">編號</span>
        <input
          class="field__input"
          type="text"
          :value="reindeer?.number ?? ''"
          :placeholder="isEdit ? '' : '儲存後自動產生'"
          disabled
        />
        <span class="field__hint">{{ isEdit ? '編號不可修改' : '編號由系統自動產生' }}</span>
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

      <div class="row">
        <label class="field">
          <span class="field__label">上次保養日期</span>
          <input
            v-model="form.lastMaintainedAt"
            class="field__input"
            type="date"
            :max="today"
            required
          />
          <span v-if="fieldErrors.lastMaintainedAt" class="field__error" role="alert">
            {{ fieldErrors.lastMaintainedAt }}
          </span>
        </label>

        <label class="field">
          <span class="field__label">下次保養日期</span>
          <input class="field__input" type="text" :value="nextMaintenance" disabled />
          <span class="field__hint">間隔 {{ MAINTENANCE_INTERVAL_MONTHS }} 個月，自動計算</span>
        </label>
      </div>

      <label class="field">
        <span class="field__label">照護專員</span>
        <select v-model="form.caretakerId" class="field__input" required>
          <option :value="null" disabled>請選擇照護專員</option>
          <option v-for="caretaker in caretakers" :key="caretaker.id" :value="caretaker.id">
            {{ caretaker.name }}（{{ caretaker.number }}）
          </option>
        </select>
        <span v-if="fieldErrors.caretakerId" class="field__error" role="alert">
          {{ fieldErrors.caretakerId }}
        </span>
      </label>

      <label class="field">
        <span class="field__label">備註</span>
        <textarea v-model="form.note" class="field__input" rows="3" maxlength="500"></textarea>
        <span v-if="fieldErrors.note" class="field__error" role="alert">{{ fieldErrors.note }}</span>
      </label>

      <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>
    </form>

    <template #footer>
      <button type="button" class="button" :disabled="isSubmitting" @click="emit('close')">取消</button>
      <button type="submit" form="reindeer-form" class="button button--primary" :disabled="!canSubmit">
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
