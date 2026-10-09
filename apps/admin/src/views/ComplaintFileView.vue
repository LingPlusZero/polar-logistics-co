<script setup lang="ts">
import { ApiError, api } from '@shared/api'
import { computed, reactive, ref } from 'vue'

const form = reactive({
  elfNumber: '',
  reason: '',
})

const fieldErrors = ref<Record<string, string>>({})
const errorMessage = ref('')
const notice = ref('')
const isSubmitting = ref(false)

const canSubmit = computed(
  () => !isSubmitting.value && form.elfNumber.trim() !== '' && form.reason.trim() !== '',
)

const submit = async () => {
  fieldErrors.value = {}
  errorMessage.value = ''
  notice.value = ''
  isSubmitting.value = true

  try {
    const receipt = await api.complaint.create({
      elfNumber: form.elfNumber.trim().toUpperCase(),
      reason: form.reason.trim(),
    })

    // 回報被申訴人姓名，讓申訴人能確認沒有輸錯編號
    notice.value = `已送出對 ${receipt.elfName}（${receipt.elfNumber}）的申訴，目前狀態為處理中。`
    form.elfNumber = ''
    form.reason = ''
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
  <section class="file">
    <h1 class="file__title">我要申訴</h1>

    <p v-if="notice" class="notice file__notice" role="status">{{ notice }}</p>

    <form class="file__form" novalidate @submit.prevent="submit">
      <label class="field">
        <span class="field__label">精靈編號</span>
        <input
          v-model="form.elfNumber"
          class="field__input"
          type="text"
          maxlength="10"
          placeholder="被申訴人的精靈編號，例如 E016"
          autocomplete="off"
          required
        />
        <span v-if="fieldErrors.elfNumber" class="field__error" role="alert">
          {{ fieldErrors.elfNumber }}
        </span>
      </label>

      <label class="field">
        <span class="field__label">申訴事由</span>
        <textarea
          v-model="form.reason"
          class="field__input"
          rows="5"
          maxlength="500"
          required
        ></textarea>
        <span class="field__hint">{{ form.reason.length }} / 500</span>
        <span v-if="fieldErrors.reason" class="field__error" role="alert">{{ fieldErrors.reason }}</span>
      </label>

      <p class="field__hint file__hint">申訴日期為送出當天，送出後由人力資源部處理。</p>
      <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>

      <button class="button button--primary file__submit" type="submit" :disabled="!canSubmit">
        送出申訴
      </button>
    </form>
  </section>
</template>

<style scoped>
.file__title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.file__notice,
.file__form {
  max-width: 32rem;
}

.file__form {
  margin-top: 1.5rem;
}

.file__hint {
  margin-bottom: 1rem;
}

.file__submit {
  width: 100%;
  padding: 0.75rem;
}
</style>
