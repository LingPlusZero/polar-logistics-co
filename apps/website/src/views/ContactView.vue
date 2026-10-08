<script setup lang="ts">
import { ref } from 'vue'
import ContactIcon from '../components/ContactIcon.vue'
import PageHero from '../components/PageHero.vue'

// 文案來源：docs/website.md「聯繫我們」
const CATEGORIES = ['一般詢問', '合作提案', '投資人關係', '人才招募', '我沒收到禮物', '其他']

// 與頁尾一致，年度固定為官網呈現的 2025
const CASE_YEAR = 2025

const form = ref({ name: '', email: '', category: '', subject: '', message: '' })
const isSubmitted = ref(false)
const caseNumber = ref('')
const queuePosition = ref(0)

// 純前端表單：不送出任何請求，只產生一組案件編號與佇列順位作為回覆
const submit = () => {
  caseNumber.value = `PLC-${CASE_YEAR}-${String(Math.floor(Math.random() * 1_000_000)).padStart(6, '0')}`
  queuePosition.value = 1_000_000 + Math.floor(Math.random() * 99_000_000)
  isSubmitted.value = true
}
</script>

<template>
  <main>
    <PageHero eyebrow="Contact" title="聯繫我們" subtitle="您的每一封來信，我們都將妥善列入處理佇列。" />

    <section class="section">
      <div class="container contact">
        <aside class="contact__info">
          <h2 class="contact__heading">聯絡資訊</h2>
          <ul class="info-list">
            <li class="info-list__item">
              <ContactIcon name="phone" class="info-list__icon" />
              <div>
                <p class="info-list__label">客服專線</p>
                <p class="info-list__value">0800-024-1224</p>
              </div>
            </li>
            <li class="info-list__item">
              <ContactIcon name="location" class="info-list__icon" />
              <div>
                <p class="info-list__label">總部</p>
                <p class="info-list__value">北緯 90 度 0 分，北極點，無門牌</p>
              </div>
            </li>
          </ul>

          <h2 class="contact__heading">其他入口</h2>
          <ul class="info-list">
            <li>
              <RouterLink to="/careers" class="info-link">
                <ContactIcon name="link" class="info-list__icon" />
                人才招募
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/investors" class="info-link">
                <ContactIcon name="link" class="info-list__icon" />
                投資人關係
              </RouterLink>
            </li>
          </ul>
        </aside>

        <div class="contact__main">
          <div v-if="isSubmitted" class="reply" role="status">
            <p class="reply__message">您的來信已進入處理佇列，預估等候時間：到明年。</p>
            <dl class="reply__meta">
              <div>
                <dt>案件編號</dt>
                <dd>{{ caseNumber }}</dd>
              </div>
              <div>
                <dt>目前佇列順位</dt>
                <dd>第 {{ queuePosition.toLocaleString('en-US') }} 位</dd>
              </div>
            </dl>
          </div>

          <form v-else class="contact__form" @submit.prevent="submit">
            <label class="contact__field">
              <span>姓名</span>
              <input v-model.trim="form.name" type="text" name="name" required maxlength="100" autocomplete="off" />
            </label>
            <label class="contact__field">
              <span>電子郵件</span>
              <input v-model.trim="form.email" type="email" name="email" required maxlength="254" autocomplete="off" />
            </label>
            <label class="contact__field">
              <span>來信類別</span>
              <select v-model="form.category" name="category" required>
                <option value="" disabled>請選擇</option>
                <option v-for="category in CATEGORIES" :key="category" :value="category">{{ category }}</option>
              </select>
            </label>
            <label class="contact__field">
              <span>主旨</span>
              <input v-model.trim="form.subject" type="text" name="subject" required maxlength="100" autocomplete="off" />
            </label>
            <label class="contact__field">
              <span>內容</span>
              <textarea v-model.trim="form.message" name="message" rows="6" required maxlength="2000" autocomplete="off"></textarea>
            </label>
            <button type="submit" class="btn btn--primary">送出</button>
          </form>
        </div>
      </div>
    </section>
  </main>
</template>

<style scoped>
.contact {
  display: grid;
  gap: 2.5rem;
}

.contact__heading {
  margin-bottom: 1rem;
  font-size: 1.125rem;
}

.info-list {
  display: grid;
  gap: 1.25rem;
  padding: 0;
  margin: 0 0 2.5rem;
  list-style: none;
}

.info-list__item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.info-list__icon {
  flex: none;
  color: var(--color-navy);
}

.info-list__label {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.info-list__value {
  font-weight: 500;
}

.info-link {
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  font-weight: 500;
  text-decoration: none;
  border-bottom: 1px solid transparent;
}

.info-link:hover {
  border-bottom-color: var(--color-navy);
}

.contact__main {
  padding: 1.5rem;
  background: #fff;
  border: 1px solid var(--color-ice-dark);
  border-top: 3px solid var(--color-navy);
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
.contact__field select,
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
.contact__field select:focus,
.contact__field textarea:focus {
  outline: 2px solid var(--color-navy);
  outline-offset: 1px;
}

.contact__form .btn {
  justify-self: start;
  cursor: pointer;
}

.reply__message {
  font-size: 1.125rem;
  font-weight: 500;
}

.reply__meta {
  display: grid;
  gap: 1rem;
  padding-top: 1rem;
  margin: 1.5rem 0 0;
  font-size: 0.875rem;
  border-top: 1px solid var(--color-ice-dark);
}

.reply__meta dt {
  color: var(--color-text-muted);
}

.reply__meta dd {
  margin: 0;
  font-variant-numeric: tabular-nums;
}

@media (min-width: 48rem) {
  .contact {
    grid-template-columns: 1fr 1.6fr;
    gap: 4rem;
    align-items: start;
  }

  .contact__main {
    padding: 2rem;
  }

  .reply__meta {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
