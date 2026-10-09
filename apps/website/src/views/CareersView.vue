<script setup lang="ts">
import { api, type Career } from '@shared/api'
import { onMounted, ref } from 'vue'
import PageHero from '../components/PageHero.vue'

// 條列欄位在資料庫以換行分隔，這裡轉成清單項目
const toLines = (text: string) => text.split('\n').filter(Boolean)

const careers = ref<Career[]>([])
const hasError = ref(false)

onMounted(async () => {
  try {
    careers.value = await api.career.list()
  } catch {
    hasError.value = true
  }
})
</script>

<template>
  <main>
    <PageHero eyebrow="Careers" title="加入極地物流，與我們一起讓每一份驚喜準時抵達" subtitle="目前在職精靈 12,408 名，今年持續擴大招募。" />

    <section class="section">
      <div class="container">
        <p v-if="hasError" class="state-message">資料載入失敗</p>

        <ul v-if="careers.length" class="jobs">
          <li v-for="career in careers" :key="career.id" class="jobs__item">
            <!-- 空值代表不限部門（docs/website.md 職缺 8 寫「各部門」） -->
            <p class="jobs__department">{{ career.department ?? '各部門' }}</p>
            <h2 class="jobs__title">{{ career.title }}</h2>

            <h3 class="jobs__label">工作內容</h3>
            <ul class="jobs__list">
              <li v-for="line in toLines(career.description)" :key="line">{{ line }}</li>
            </ul>

            <h3 class="jobs__label">任職資格</h3>
            <ul class="jobs__list">
              <li v-for="line in toLines(career.requirements)" :key="line">{{ line }}</li>
            </ul>

            <!-- 福利包含轉正機會；只有一項時維持單行文字，多行時顯示成清單 -->
            <template v-if="career.benefits">
              <h3 class="jobs__label">福利</h3>
              <ul v-if="toLines(career.benefits).length > 1" class="jobs__list">
                <li v-for="line in toLines(career.benefits)" :key="line">{{ line }}</li>
              </ul>
              <p v-else>{{ career.benefits }}</p>
            </template>

            <template v-if="career.note">
              <h3 class="jobs__label">備註</h3>
              <p>{{ career.note }}</p>
            </template>
          </li>
        </ul>

        <div class="disclaimer">
          <p>本公司為機會均等雇主。人類申請者須先通過「目擊後處理程序」，結果不另行通知。</p>
          <p>歷年離職率：0%（不含失蹤）</p>
        </div>
      </div>
    </section>
  </main>
</template>

<style scoped>
.state-message {
  color: var(--color-text-muted);
}

.jobs {
  display: grid;
  gap: 1.5rem;
  padding: 0;
  margin: 0 0 3rem;
  list-style: none;
}

.jobs__item {
  padding: 1.5rem;
  background: #fff;
  border: 1px solid var(--color-ice-dark);
  border-left: 3px solid var(--color-navy);
}

.jobs__department {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.jobs__title {
  font-size: 1.25rem;
}

.jobs__label {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

/* 條列項目 */
.jobs__list {
  padding-left: 1.25rem;
  margin: 0;
}

.disclaimer {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

@media (min-width: 48rem) {
  .jobs {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
