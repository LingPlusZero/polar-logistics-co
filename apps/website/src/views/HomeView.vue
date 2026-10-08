<script setup lang="ts">
import CountUp from '../components/CountUp.vue'
import DeadlineCountdown from '../components/DeadlineCountdown.vue'
import DepartmentCarousel from '../components/DepartmentCarousel.vue'
import HeroBackdrop from '../components/HeroBackdrop.vue'
import ValueIcon from '../components/ValueIcon.vue'
import { CORE_VALUES, MILESTONES } from '../data/about'

// 文案來源：docs/website.md「Banner / 關鍵資訊」
const BANNER_FACTS = [
  { label: '成立', value: '西元 1823 年' },
  { label: '總部', value: '北緯 90 度 0 分' },
  { label: '唯一交貨期限', value: '每年 12/24' },
]

// 文案來源：docs/website.md「數據列」
const STATS = [
  { target: 22.3, decimals: 1, suffix: ' 億份', title: '上年度配送禮物數', note: '2025 年度' },
  { target: 6.7, decimals: 1, prefix: '+', suffix: '%', title: '年成長率', note: '連續 202 年正成長' },
  { target: 99.97, decimals: 2, suffix: '%', title: '準時送達率', note: '12/25 零點前抵達' },
  { target: 12408, decimals: 0, suffix: ' 名', title: '在職精靈', note: '不含實習精靈和馴鹿' },
]
</script>

<template>
  <main>
    <section class="banner">
      <HeroBackdrop mark />
      <div class="container banner__content">
        <h1 class="banner__title">讓每一份驚喜，準時抵達</h1>
        <p class="banner__subtitle">自 1823 年起，極地物流持續為全球家庭提供值得信賴的年度配送服務。</p>
        <div class="banner__actions">
          <RouterLink to="/investors" class="btn btn--primary">查看營運績效</RouterLink>
          <RouterLink to="/careers" class="btn btn--outline">加入我們</RouterLink>
        </div>

        <dl class="banner__facts">
          <div v-for="fact in BANNER_FACTS" :key="fact.label">
            <dt>{{ fact.label }}</dt>
            <dd>{{ fact.value }}</dd>
          </div>
          <div>
            <dt>距離本年度交貨期限</dt>
            <dd><DeadlineCountdown /></dd>
          </div>
        </dl>
      </div>
    </section>

    <section class="stats">
      <div class="container">
        <dl class="stats__list">
          <div v-for="stat in STATS" :key="stat.title" class="stats__item">
            <dt class="stats__number">
              <CountUp
                :target="stat.target"
                :decimals="stat.decimals"
                :prefix="stat.prefix"
                :suffix="stat.suffix"
              />
            </dt>
            <dd class="stats__title">{{ stat.title }}</dd>
            <dd class="stats__note">{{ stat.note }}</dd>
          </div>
        </dl>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <h2 class="section__title">關於我們</h2>
        <div class="about">
          <article class="about__block">
            <h3 class="about__heading">公司沿革</h3>
            <ol class="timeline">
              <li v-for="milestone in MILESTONES" :key="milestone.year" class="timeline__item">
                <span class="timeline__year">{{ milestone.year }}</span>
                <span>{{ milestone.event }}</span>
              </li>
            </ol>
          </article>

          <article class="about__block">
            <h3 class="about__heading">願景使命</h3>
            <ul class="values">
              <li v-for="value in CORE_VALUES" :key="value.title" class="values__item">
                <ValueIcon :name="value.icon" class="values__icon" />
                <div>
                  <h4>{{ value.title }}</h4>
                  <p>{{ value.description }}</p>
                </div>
              </li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <section class="section section--white">
      <div class="container">
        <h2 class="section__title">五大業務</h2>
        <DepartmentCarousel />
      </div>
    </section>
  </main>
</template>

<style scoped>
.banner {
  position: relative;
  overflow: hidden;
  padding: 4rem 0 0;
  color: var(--color-ice);
  background: var(--color-navy);
  border-bottom: 2px solid var(--color-red);
}

.banner__content {
  position: relative;
}

.banner__title {
  font-size: 2rem;
  line-height: 1.3;
  letter-spacing: 0.05em;
}

.banner__subtitle {
  margin-top: 1.25rem;
  font-size: 1.0625rem;
  color: var(--color-ice-dark);
}

.banner__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 2.5rem;
}

/* 關鍵資訊列：與標題區之間以細線分隔 */
.banner__facts {
  display: grid;
  gap: 1rem;
  padding: 1.5rem 0 2rem;
  margin: 3.5rem 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.18);
}

.banner__facts dt {
  font-size: 0.8125rem;
  letter-spacing: 0.1em;
  color: var(--color-ice-dark);
}

.banner__facts dd {
  margin: 0.125rem 0 0;
  font-size: 1.125rem;
  font-weight: 500;
}

/* 與前一區塊交錯底色 */
.section--white {
  background: #fff;
}

.about {
  display: grid;
  gap: 3rem;
}

.about__heading {
  margin-bottom: 1.25rem;
  font-size: 1.25rem;
}

.timeline {
  padding: 0;
  margin: 0;
  list-style: none;
  border-left: 2px solid var(--color-ice-dark);
}

.timeline__item {
  position: relative;
  display: grid;
  gap: 0.25rem;
  padding: 0 0 1.25rem 1.5rem;
}

/* 時間軸上的圓點 */
.timeline__item::before {
  content: "";
  position: absolute;
  top: 0.6rem;
  left: -0.4375rem;
  width: 0.75rem;
  height: 0.75rem;
  background: var(--color-navy);
  border-radius: 50%;
}

.timeline__item:last-child::before {
  background: var(--color-red);
}

.timeline__year {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.values {
  display: grid;
  gap: 1rem;
  padding: 0;
  margin: 0;
  list-style: none;
}

.values__item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: #fff;
  border: 1px solid var(--color-ice-dark);
  border-left: 3px solid var(--color-navy);
}

.values__icon {
  flex: none;
  color: var(--color-navy);
}

.values__item h4 {
  margin: 0 0 0.25rem;
  font-size: 1.0625rem;
}

.values__item p {
  font-size: 0.9375rem;
  color: var(--color-text-muted);
}

.stats {
  padding: 3rem 0;
  background: #fff;
  border-top: 1px solid var(--color-ice-dark);
  border-bottom: 1px solid var(--color-ice-dark);
}

.stats__list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2rem 1rem;
  margin: 0;
}

.stats__number {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}

.stats__title {
  margin: 0.5rem 0 0;
  font-weight: 500;
}

.stats__note {
  margin: 0.125rem 0 0;
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

@media (min-width: 48rem) {
  .banner {
    padding: 6rem 0 0;
  }

  .banner__title {
    font-size: 3.25rem;
  }

  .banner__subtitle {
    font-size: 1.25rem;
  }
  .banner__facts {
    grid-template-columns: repeat(3, 1fr) 1.6fr;
    gap: 2rem;
  }

  .timeline__item {
    grid-template-columns: 4rem 1fr;
    gap: 1rem;
  }

  /* 左側時間軸較寬，右側價值卡直向堆疊，避免兩側留白 */
  .about {
    grid-template-columns: 7fr 5fr;
    gap: 4rem;
    align-items: start;
  }

  .stats__list {
    grid-template-columns: repeat(4, 1fr);
  }

  .stats__number {
    font-size: 2.25rem;
  }
}
</style>
