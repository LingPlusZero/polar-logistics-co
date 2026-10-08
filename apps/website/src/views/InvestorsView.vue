<script setup lang="ts">
import { api, type AnnualStatic } from '@shared/api'
import type { EChartsCoreOption } from 'echarts/core'
import { computed, onMounted, ref } from 'vue'
import EChart from '../components/EChart.vue'
import { CHAIRMAN_LETTER } from '../data/chairman'
import PageHero from '../components/PageHero.vue'

const formatGifts = (value: number) => `${(value / 100_000_000).toFixed(1)} 億份`
const formatRate = (value: number) => `${value.toFixed(2)}%`
const formatGrowth = (value: number) => `+${value.toFixed(1)}%`

// 指標說明，文案來源：docs/website.md「營運表現突破與成長動能」
const METRIC_NOTES = [
  { title: '年配送禮物數', description: '每年 12/24 當夜送出總數', hasAsterisk: true },
  { title: '年成長率', description: '與去年相比' },
  { title: '準時送達率', description: '12/25 零點前抵達' },
  { title: '完整送達率', description: '正確送到正確的人' },
]

// 北風計畫對照表，文案來源：docs/website.md
const FLEET_COMPARISON = [
  { item: '夜間照明', previous: '標準', ninth: '前導紅光強化' },
  { item: '單趟續航', previous: '全球一圈', ninth: '全球一圈' },
  { item: '飛行穩定性', previous: '良好', ninth: '優良' },
  { item: '維護週期', previous: '每日', ninth: '每週' },
]

// 愛心同行文案，來源：docs/website.md
const CARE_PROGRAMS = [
  {
    title: '據點陪伴',
    description: '每年 12 月起，由資深精靈組成關懷小組，走訪兒童醫院、育幼機構與偏鄉學校，了解每一位孩子的心願。',
  },
  {
    title: '心願傳遞',
    description: '孩子們的心願將統一送交乖寶寶稽核部審核，並依結果納入年度配送名單。',
  },
  {
    title: '無障礙入戶',
    description: '針對無煙囪住宅，外勤機動部提供「替代入口評估機制」，確保每個家庭都不被遺漏。',
  },
]

const COLORS = {
  gifts: '#0B2545',
  growth: '#C8102E',
  onTime: '#3E7CB1',
  complete: '#8FB4D9',
  feedback: '#7A8B99',
}

// 低於此寬度時只保留一條右側座標軸，避免三條軸把繪圖區擠得太窄
const NARROW_WIDTH = 640

const statics = ref<AnnualStatic[]>([])
const hasError = ref(false)

// 表格由新到舊，折線圖由舊到新
const tableRows = computed(() => [...statics.value].reverse())

// 五個指標單位不同：禮物數（億份）用左軸，成長率與送達率（%）分別用兩條右軸，
// 送達率軸固定 99–100%，才不會把微幅波動放大成劇烈起伏
const buildOption = (width: number): EChartsCoreOption => {
  const isNarrow = width < NARROW_WIDTH
  const line = (name: string, key: keyof AnnualStatic, color: string, yAxisIndex: number, valueFormatter: (v: number) => string, extra = {}) => ({
    name,
    type: 'line',
    yAxisIndex,
    data: statics.value.map((row) => row[key]),
    itemStyle: { color },
    lineStyle: { color, width: 2 },
    symbolSize: 6,
    tooltip: { valueFormatter },
    ...extra,
  })

  const axisLabelStyle = { color: '#5B6B7F', fontSize: 11 }
  const sideAxis = (min: number, max: number, interval: number, unit: string, color: string, offset = 0, show = true) => ({
    type: 'value',
    position: 'right',
    min,
    max,
    interval,
    offset,
    show,
    splitLine: { show: false },
    axisLine: { show: true, lineStyle: { color } },
    axisLabel: { ...axisLabelStyle, color, formatter: `{value}${unit}` },
  })

  return {
    textStyle: { fontFamily: 'Noto Sans TC, sans-serif' },
    color: Object.values(COLORS),
    legend: {
      top: 0,
      itemWidth: 18,
      textStyle: { fontSize: isNarrow ? 11 : 13 },
    },
    tooltip: { trigger: 'axis' },
    grid: {
      top: isNarrow ? 72 : 48,
      bottom: 28,
      left: isNarrow ? 40 : 56,
      right: isNarrow ? 44 : 110,
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: statics.value.map((row) => String(row.year)),
      axisLabel: axisLabelStyle,
      axisLine: { lineStyle: { color: '#DCE6EE' } },
    },
    yAxis: [
      {
        type: 'value',
        position: 'left',
        min: 10,
        max: 25,
        interval: 5,
        splitLine: { lineStyle: { color: '#DCE6EE' } },
        axisLine: { show: true, lineStyle: { color: COLORS.gifts } },
        axisLabel: { ...axisLabelStyle, color: COLORS.gifts, formatter: '{value}億' },
      },
      sideAxis(0, 10, 2, '%', COLORS.growth),
      sideAxis(99, 100, 0.25, '%', COLORS.onTime, 56, !isNarrow),
    ],
    series: [
      // 配送數字旁的「*」對應下方註腳
      line('年配送禮物數*', 'giftsDelivered', COLORS.gifts, 0, formatGifts, {
        data: statics.value.map((row) => Number((row.giftsDelivered / 100_000_000).toFixed(1))),
        lineStyle: { color: COLORS.gifts, width: 3 },
        tooltip: { valueFormatter: (v: number) => `${v.toFixed(1)} 億份` },
      }),
      line('年成長率', 'growthRate', COLORS.growth, 1, formatGrowth),
      line('準時送達率', 'onTimeRate', COLORS.onTime, 2, formatRate),
      line('完整送達率', 'completeRate', COLORS.complete, 2, formatRate),
      line('體驗回饋率', 'feedbackRate', COLORS.feedback, 2, formatRate, {
        lineStyle: { color: COLORS.feedback, width: 2, type: 'dashed' },
      }),
    ],
  }
}

onMounted(async () => {
  try {
    statics.value = await api.statics.annual()
  } catch {
    hasError.value = true
  }
})
</script>

<template>
  <main>
    <PageHero eyebrow="Investor Relations" title="精準執行，續創佳績" subtitle="驅動創新轉型，再創營收與獲利新紀錄" />

    <section class="section section--white">
      <div class="container">
        <h2 class="section__title">董事長致詞</h2>
        <div class="letter">
          <p>{{ CHAIRMAN_LETTER.greeting }}</p>
          <p class="letter__lead">{{ CHAIRMAN_LETTER.paragraphs[0] }}</p>
          <p v-for="paragraph in CHAIRMAN_LETTER.paragraphs.slice(1)" :key="paragraph">{{ paragraph }}</p>
          <p class="letter__signature">{{ CHAIRMAN_LETTER.signature }}</p>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <h2 class="section__title">營運表現突破與成長動能</h2>

        <p v-if="hasError" class="state-message">資料載入失敗</p>

        <template v-if="statics.length">
          <div class="chart-card">
            <!-- key 讓資料載入後重建，確保圖表用最新資料初始化 -->
            <EChart
              :key="statics.length"
              :build-option="buildOption"
              label="近十年營運表現折線圖：年配送禮物數、年成長率、準時送達率、完整送達率、體驗回饋率"
            />
            <dl class="metric-notes">
              <div v-for="note in METRIC_NOTES" :key="note.title" class="metric-notes__item">
                <dt>{{ note.title }}<sup v-if="note.hasAsterisk">*</sup></dt>
                <dd>{{ note.description }}</dd>
              </div>
            </dl>
          </div>

          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th scope="col">年度</th>
                  <th scope="col">年配送禮物數<sup>*</sup></th>
                  <th scope="col">年成長率</th>
                  <th scope="col">準時送達率</th>
                  <th scope="col">完整送達率</th>
                  <th scope="col">體驗回饋率</th>
                  <th scope="col">備註</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in tableRows" :key="row.year">
                  <th scope="row">{{ row.year }}</th>
                  <td>{{ formatGifts(row.giftsDelivered) }}</td>
                  <td>{{ formatGrowth(row.growthRate) }}</td>
                  <td>{{ formatRate(row.onTimeRate) }}</td>
                  <td>{{ formatRate(row.completeRate) }}</td>
                  <td>{{ formatRate(row.feedbackRate) }}</td>
                  <td>{{ row.note }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="footnote">* 不含未被乖寶寶稽核部通過之名單。</p>
        </template>
      </div>
    </section>

    <section class="section section--white">
      <div class="container">
        <h2 class="section__title">「北風計畫」－持續投資車隊，提升每一趟旅程的品質</h2>
        <div class="prose prose--full">
          <p>極地物流宣布「北風計畫」：全面導入第九代飛行動力單元！</p>
          <p>為因應逐年成長的配送量，本公司宣布「北風計畫」，預計於年底前完成全車隊升級。</p>
          <p>新一代動力單元具備更佳的續航力、更穩定的夜間視野，並全面搭載「前導照明模組」。</p>
        </div>

        <div class="table-wrapper">
          <table class="data-table data-table--comparison">
            <thead>
              <tr>
                <th scope="col">項目</th>
                <th scope="col">前八代</th>
                <th scope="col">第九代</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in FLEET_COMPARISON" :key="row.item">
                <th scope="row">{{ row.item }}</th>
                <td>{{ row.previous }}</td>
                <td>{{ row.ninth }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="footnote">前八代動力單元之後續安排，將依相關法規及內部評估辦理。</p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <h2 class="section__title">「愛心同行」－深入社會關懷據點，用愛與魔法陪伴孩子</h2>
        <div class="prose prose--full">
          <p>極地物流相信，企業的成長應與社會共享。自 1823 年起，我們持續深入全球各地的兒童關懷據點，以長期、穩定的陪伴，讓每一個孩子都被看見。</p>
        </div>

        <ul class="care">
          <li v-for="program in CARE_PROGRAMS" :key="program.title" class="care__item">
            <h3 class="care__title">{{ program.title }}</h3>
            <p>{{ program.description }}</p>
          </li>
        </ul>
        <p class="footnote">參與資格以乖寶寶稽核部審核結果為準。</p>
      </div>
    </section>
  </main>
</template>

<style scoped>
.section--white {
  background: #fff;
}

/* 董事長致詞：稱謂後第一段作為開場，字級略大 */
.letter p + p {
  margin-top: 1.25rem;
}

.letter__lead {
  font-size: 1.125rem;
}

.letter__signature {
  text-align: right;
  font-weight: 500;
}

.state-message {
  color: var(--color-text-muted);
}

.chart-card {
  padding: 1rem;
  margin-bottom: 2.5rem;
  background: #fff;
  border: 1px solid var(--color-ice-dark);
}

.metric-notes {
  display: grid;
  gap: 0.5rem 2rem;
  padding-top: 1rem;
  margin: 1rem 0 0;
  font-size: 0.875rem;
  border-top: 1px solid var(--color-ice-dark);
}

.metric-notes dt {
  font-weight: 500;
}

.metric-notes dt sup {
  color: var(--color-red);
}

.metric-notes dd {
  margin: 0;
  color: var(--color-text-muted);
}

.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  min-width: 36rem;
  font-size: 0.9375rem;
  text-align: right;
  border-collapse: collapse;
  font-variant-numeric: tabular-nums;
}

.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-ice-dark);
}

.data-table thead th {
  font-weight: 500;
  color: var(--color-ice);
  background: var(--color-navy);
}

.data-table th:first-child,
.data-table thead th:last-child,
.data-table td:last-child {
  text-align: left;
}

.data-table sup {
  color: #ff8a9a;
}

.data-table--comparison {
  min-width: 0;
  text-align: left;
}

.footnote {
  margin-top: 1rem;
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

.prose {
  max-width: 42rem;
  margin-bottom: 2rem;
}

/* 段落撐滿容器寬度，與下方表格、卡片對齊 */
.prose--full {
  max-width: none;
}

.prose p + p {
  margin-top: 0.75rem;
}

.care {
  display: grid;
  gap: 1.5rem;
  padding: 0;
  margin: 0;
  list-style: none;
}

.care__item {
  padding: 1.5rem;
  background: #fff;
  border: 1px solid var(--color-ice-dark);
  border-top: 3px solid var(--color-navy);
}

.care__title {
  margin-bottom: 0.5rem;
  font-size: 1.125rem;
}

@media (min-width: 48rem) {
  .chart-card {
    padding: 1.5rem;
  }

  .metric-notes {
    grid-template-columns: repeat(2, 1fr);
  }

  .care {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
