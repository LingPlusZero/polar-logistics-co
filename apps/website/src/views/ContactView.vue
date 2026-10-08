<script setup lang="ts">
import { ref } from 'vue'
import PageHero from '../components/PageHero.vue'

const form = ref({ name: '', email: '', subject: '', message: '' })
const isSubmitted = ref(false)

// 純前端表單：不送出任何請求，只切換成回覆訊息
const submit = () => {
  isSubmitted.value = true
}
</script>

<template>
  <main>
    <PageHero eyebrow="Contact" title="聯繫我們" subtitle="您的每一封來信，我們都將妥善列入處理佇列。" />

    <section class="section">
      <div class="container contact">
        <p v-if="isSubmitted" class="contact__reply" role="status">
          您的來信已進入處理佇列，預估等候時間：到明年。
        </p>

        <form v-else class="contact__form" @submit.prevent="submit">
          <label class="contact__field">
            <span>姓名</span>
            <input v-model.trim="form.name" type="text" name="name" required maxlength="100" autocomplete="name" />
          </label>
          <label class="contact__field">
            <span>電子郵件</span>
            <input v-model.trim="form.email" type="email" name="email" required maxlength="254" autocomplete="email" />
          </label>
          <label class="contact__field">
            <span>主旨</span>
            <input v-model.trim="form.subject" type="text" name="subject" required maxlength="100" />
          </label>
          <label class="contact__field">
            <span>內容</span>
            <textarea v-model.trim="form.message" name="message" rows="6" required maxlength="2000"></textarea>
          </label>
          <button type="submit" class="btn btn--primary">送出</button>
        </form>
      </div>
    </section>
  </main>
</template>

<style scoped>
.contact {
  max-width: 40rem;
  margin: 0 auto;
}

.contact__form {
  display: grid;
  gap: 1.25rem;
}

.contact__field {
  display: grid;
  gap: 0.375rem;
  font-weight: 500;
}

.contact__field input,
.contact__field textarea {
  width: 100%;
  padding: 0.75rem;
  font: inherit;
  font-weight: 400;
  color: var(--color-navy);
  background: #fff;
  border: 1px solid var(--color-ice-dark);
  border-radius: 0.125rem;
}

.contact__field input:focus,
.contact__field textarea:focus {
  outline: 2px solid var(--color-navy);
  outline-offset: 1px;
}

.contact__form .btn {
  justify-self: start;
  cursor: pointer;
}

.contact__reply {
  padding: 1.5rem;
  background: #fff;
  border-left: 3px solid var(--color-red);
}
</style>
