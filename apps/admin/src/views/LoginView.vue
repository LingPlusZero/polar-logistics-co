<script setup lang="ts">
import BrandLogo from '@shared/components/BrandLogo.vue'
import { ApiError, isDemoMode } from '@shared/api'
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const route = useRoute()
const router = useRouter()
const { login } = useAuth()

const number = ref('')
const password = ref('')
const errorMessage = ref('')

// 從其他頁面導回登入頁時帶的提示（例如改完密碼）
const NOTICES: Record<string, string> = {
  'password-changed': '密碼已更新，請使用新密碼重新登入。',
  'idle-timeout': '您已閒置超過 30 分鐘，為了帳號安全已自動登出，請重新登入。',
  'session-expired': '登入已失效，請重新登入。',
}

const notice = typeof route.query.notice === 'string' ? (NOTICES[route.query.notice] ?? '') : ''
const isSubmitting = ref(false)

// 只接受站內路徑，避免被帶去其他網站
const redirectTarget = () => {
  const redirect = route.query.redirect
  return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')
    ? redirect
    : '/'
}

const submit = async () => {
  errorMessage.value = ''

  isSubmitting.value = true

  try {
    await login(number.value.trim().toUpperCase(), password.value)
    await router.replace(redirectTarget())
  } catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : '無法連線，請稍後再試'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <main class="login">
    <form class="login__card" novalidate @submit.prevent="submit">
      <div class="login__brand">
        <BrandLogo :size="40" />
        <div>
          <p class="login__company">極地物流股份有限公司</p>
          <h1 class="login__title">精靈管理系統</h1>
        </div>
      </div>

      <label class="login__field">
        <span class="login__label">精靈編號</span>
        <input
          v-model="number"
          class="login__input"
          type="text"
          autocomplete="username"
          maxlength="10"
          required
          autofocus
        />
      </label>

      <label class="login__field">
        <span class="login__label">密碼</span>
        <input
          v-model="password"
          class="login__input"
          type="password"
          autocomplete="current-password"
          maxlength="72"
          required
        />
      </label>

      <p v-if="notice" class="login__notice" role="status">{{ notice }}</p>
      <p v-if="errorMessage" class="login__error" role="alert">{{ errorMessage }}</p>

      <button class="login__submit" type="submit" :disabled="isSubmitting || !number || !password">
        登入
      </button>

      <p v-if="isDemoMode" class="login__hint">
        Demo 帳號為 E001–E022，密碼輸入自己的帳號即可。
      </p>
    </form>
  </main>
</template>

<style scoped>
.login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  /* 避開固定在頂部的 Demo 橫幅 */
  padding: calc(var(--banner-height) + 1.5rem) 1rem 1.5rem;
  background: var(--color-navy);
}

.login__card {
  width: 100%;
  max-width: 24rem;
  padding: 2rem 1.5rem;
  background: var(--color-ice);
}

.login__brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.login__company {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

.login__title {
  font-size: 1.375rem;
  line-height: 1.4;
}

.login__field {
  display: block;
  margin-bottom: 1rem;
}

.login__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.login__input {
  width: 100%;
  padding: 0.625rem 0.75rem;
  font: inherit;
  color: var(--color-navy);
  background: #fff;
  border: 1px solid var(--color-ice-dark);
}

.login__input:focus-visible {
  outline: 2px solid var(--color-navy);
  outline-offset: 1px;
}

.login__notice {
  margin-bottom: 1rem;
  padding: 0.625rem 0.75rem;
  font-size: 0.875rem;
  background: var(--color-ice-dark);
  border-left: 3px solid var(--color-navy);
}

.login__error {
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: var(--color-red);
}

.login__submit {
  width: 100%;
  padding: 0.75rem;
  font: inherit;
  color: var(--color-ice);
  background: var(--color-navy);
  border: 0;
  cursor: pointer;
}

.login__submit:hover:not(:disabled) {
  background: var(--color-navy-light);
}

.login__submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.login__hint {
  margin-top: 1rem;
  font-size: 0.75rem;
  color: var(--color-text-muted);
}
</style>
