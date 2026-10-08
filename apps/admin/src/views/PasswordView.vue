<script setup lang="ts">
import { ApiError, PASSWORD_RULES, api, isDemoMode } from '@shared/api'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { logout } = useAuth()

const form = reactive({
  oldPassword: '',
  newPassword: '',
  newPasswordConfirmation: '',
})

// 欄位層級的錯誤訊息（後端或 Demo 回傳的 422）
const fieldErrors = ref<Record<string, string>>({})
const errorMessage = ref('')
const isSubmitting = ref(false)

const rules = computed(() =>
  PASSWORD_RULES.map((rule) => ({ label: rule.label, passed: rule.test(form.newPassword) })),
)

const isConfirmMismatch = computed(
  () => form.newPasswordConfirmation !== '' && form.newPasswordConfirmation !== form.newPassword,
)

const canSubmit = computed(
  () =>
    !isSubmitting.value &&
    form.oldPassword !== '' &&
    rules.value.every((rule) => rule.passed) &&
    form.newPasswordConfirmation === form.newPassword,
)

const submit = async () => {
  fieldErrors.value = {}
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await api.auth.changePassword(form.oldPassword, form.newPassword, form.newPasswordConfirmation)
    // 後端已讓所有登入失效，本機也清掉登入狀態，回登入頁用新密碼重新登入
    await logout()
    await router.replace({ name: 'login', query: { notice: 'password-changed' } })
  } catch (error) {
    if (error instanceof ApiError && Object.keys(error.errors).length > 0) {
      // 每個欄位只顯示第一則訊息
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
  <section class="password">
    <h1 class="password__title">修改密碼</h1>

    <p v-if="isDemoMode" class="password__demo">
      Demo 模式：預設密碼為自己的精靈編號。修改後的密碼只保存在這個分頁，關閉分頁後恢復為預設密碼。
    </p>

    <form class="password__form" novalidate @submit.prevent="submit">
      <label class="field">
        <span class="field__label">舊密碼</span>
        <input
          v-model="form.oldPassword"
          class="field__input"
          type="password"
          autocomplete="current-password"
          maxlength="72"
          required
        />
        <span v-if="fieldErrors.oldPassword" class="field__error" role="alert">
          {{ fieldErrors.oldPassword }}
        </span>
      </label>

      <label class="field">
        <span class="field__label">新密碼</span>
        <input
          v-model="form.newPassword"
          class="field__input"
          type="password"
          autocomplete="new-password"
          maxlength="72"
          required
        />
        <span v-if="fieldErrors.newPassword" class="field__error" role="alert">
          {{ fieldErrors.newPassword }}
        </span>
      </label>

      <ul class="rules" aria-label="密碼規則">
        <li
          v-for="rule in rules"
          :key="rule.label"
          class="rules__item"
          :class="{ 'rules__item--passed': rule.passed }"
        >
          {{ rule.label }}
        </li>
      </ul>

      <label class="field">
        <span class="field__label">再確認一次新密碼</span>
        <input
          v-model="form.newPasswordConfirmation"
          class="field__input"
          type="password"
          autocomplete="new-password"
          maxlength="72"
          required
        />
        <span v-if="isConfirmMismatch || fieldErrors.newPasswordConfirmation" class="field__error" role="alert">
          {{ fieldErrors.newPasswordConfirmation ?? '兩次輸入的新密碼不一致' }}
        </span>
      </label>

      <p v-if="errorMessage" class="password__error" role="alert">{{ errorMessage }}</p>

      <button class="password__submit" type="submit" :disabled="!canSubmit">更新密碼</button>
    </form>
  </section>
</template>

<style scoped>
.password__title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.password__demo {
  max-width: 24rem;
  margin-top: 0.75rem;
  padding: 0.625rem 0.75rem;
  font-size: 0.8125rem;
  color: var(--color-navy);
  background: var(--color-ice-dark);
  border-left: 3px solid var(--color-red);
}

.password__form {
  max-width: 24rem;
  margin-top: 1.5rem;
}

.field {
  display: block;
  margin-bottom: 1rem;
}

.field__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.field__input {
  width: 100%;
  padding: 0.625rem 0.75rem;
  font: inherit;
  color: var(--color-navy);
  background: #fff;
  border: 1px solid var(--color-ice-dark);
}

.field__input:focus-visible {
  outline: 2px solid var(--color-navy);
  outline-offset: 1px;
}

.field__error,
.password__error {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.8125rem;
  color: var(--color-red);
}

.rules {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem 1rem;
  margin: -0.5rem 0 1rem;
  padding: 0;
  list-style: none;
}

.rules__item {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

/* 達成的規則不只靠顏色，加上前綴符號 */
.rules__item::before {
  content: '○ ';
}

.rules__item--passed {
  color: var(--color-navy);
  font-weight: 700;
}

.rules__item--passed::before {
  content: '● ';
}


.password__submit {
  width: 100%;
  padding: 0.75rem;
  font: inherit;
  color: var(--color-ice);
  background: var(--color-navy);
  border: 0;
  cursor: pointer;
}

.password__submit:hover:not(:disabled) {
  background: var(--color-navy-light);
}

.password__submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
