<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import type { Career, Department } from '@shared/api'
import { computed, reactive, ref, watch } from 'vue'
import ModalDialog from './ModalDialog.vue'

const props = defineProps<{
  open: boolean
  // 有帶職缺就是編輯，沒有就是新增
  career: Career | null
  departments: Department[]
}>()

const emit = defineEmits<{
  close: []
  saved: [career: Career]
}>()

const isEdit = computed(() => props.career !== null)

const form = reactive({
  title: '',
  // null＝不限部門
  departmentId: null as number | null,
  description: '',
  requirements: '',
  benefits: '',
  note: '',
})

const fieldErrors = ref<Record<string, string>>({})
const errorMessage = ref('')
const isSubmitting = ref(false)

// 每次開啟都重填表單，避免殘留上一次的內容與錯誤
watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return
    }

    fieldErrors.value = {}
    errorMessage.value = ''
    form.title = props.career?.title ?? ''
    form.departmentId = props.career?.departmentId ?? null
    form.description = props.career?.description ?? ''
    form.requirements = props.career?.requirements ?? ''
    form.benefits = props.career?.benefits ?? ''
    form.note = props.career?.note ?? ''
  },
)

const canSubmit = computed(
  () =>
    !isSubmitting.value &&
    form.title.trim() !== '' &&
    form.description.trim() !== '' &&
    form.requirements.trim() !== '',
)

// 選填欄位留空就送 null
const optional = (value: string) => (value.trim() === '' ? null : value.trim())

const submit = async () => {
  fieldErrors.value = {}
  errorMessage.value = ''
  isSubmitting.value = true

  const input = {
    title: form.title.trim(),
    departmentId: form.departmentId,
    description: form.description.trim(),
    requirements: form.requirements.trim(),
    benefits: optional(form.benefits),
    note: optional(form.note),
  }

  try {
    const saved = props.career
      ? await api.career.update(props.career.id, input)
      : await api.career.create(input)

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
  <ModalDialog :open="open" :title="isEdit ? '修改職缺' : '新增職缺'" @close="emit('close')">
    <form id="career-form" novalidate @submit.prevent="submit">
      <label class="field">
        <span class="field__label">職缺名稱</span>
        <input
          v-model="form.title"
          class="field__input"
          type="text"
          maxlength="100"
          autocomplete="off"
          required
        />
        <span v-if="fieldErrors.title" class="field__error" role="alert">{{ fieldErrors.title }}</span>
      </label>

      <label class="field">
        <span class="field__label">職缺部門</span>
        <select v-model="form.departmentId" class="field__input">
          <option :value="null">不限部門</option>
          <option v-for="department in departments" :key="department.id" :value="department.id">
            {{ department.name }}
          </option>
        </select>
        <span v-if="fieldErrors.departmentId" class="field__error" role="alert">
          {{ fieldErrors.departmentId }}
        </span>
      </label>

      <label class="field">
        <span class="field__label">工作內容</span>
        <textarea
          v-model="form.description"
          class="field__input"
          rows="5"
          maxlength="5000"
          required
        ></textarea>
        <span class="field__hint">一行一項，官網會顯示成條列</span>
        <span v-if="fieldErrors.description" class="field__error" role="alert">
          {{ fieldErrors.description }}
        </span>
      </label>

      <label class="field">
        <span class="field__label">任職資格</span>
        <textarea
          v-model="form.requirements"
          class="field__input"
          rows="5"
          maxlength="5000"
          required
        ></textarea>
        <span class="field__hint">一行一項，官網會顯示成條列</span>
        <span v-if="fieldErrors.requirements" class="field__error" role="alert">
          {{ fieldErrors.requirements }}
        </span>
      </label>

      <label class="field">
        <span class="field__label">福利（選填）</span>
        <textarea v-model="form.benefits" class="field__input" rows="3" maxlength="2000"></textarea>
        <span class="field__hint">可一行一項（例如轉正機會也寫在這裡），官網多行時會顯示成條列</span>
        <span v-if="fieldErrors.benefits" class="field__error" role="alert">{{ fieldErrors.benefits }}</span>
      </label>

      <label class="field">
        <span class="field__label">備註（選填）</span>
        <textarea v-model="form.note" class="field__input" rows="2" maxlength="2000"></textarea>
        <span v-if="fieldErrors.note" class="field__error" role="alert">{{ fieldErrors.note }}</span>
      </label>

      <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>
    </form>

    <template #footer>
      <button type="button" class="button" :disabled="isSubmitting" @click="emit('close')">取消</button>
      <button type="submit" form="career-form" class="button button--primary" :disabled="!canSubmit">
        {{ isEdit ? '儲存' : '新增' }}
      </button>
    </template>
  </ModalDialog>
</template>
