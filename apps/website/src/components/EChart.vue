<script setup lang="ts">
import { LineChart } from 'echarts/charts'
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components'
import { init, use, type ECharts, type EChartsCoreOption } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { onBeforeUnmount, onMounted, ref } from 'vue'

// 只註冊會用到的元件，減少打包體積
use([LineChart, GridComponent, LegendComponent, TooltipComponent, CanvasRenderer])

const props = defineProps<{
  // 以容器寬度產生設定，窄螢幕時可簡化座標軸
  buildOption: (width: number) => EChartsCoreOption
  label: string
}>()

const root = ref<HTMLElement | null>(null)

let chart: ECharts | null = null
let resizeObserver: ResizeObserver | null = null
let visibilityObserver: IntersectionObserver | null = null

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches

// 只有第一次繪製（進入畫面時）才播放動畫；之後因 RWD 重算設定時關閉，避免曲線重畫一次
const render = (animate: boolean) => {
  if (chart && root.value) {
    chart.setOption(
      { ...props.buildOption(root.value.clientWidth), animation: animate && !reduceMotion() },
      true,
    )
  }
}

const start = () => {
  if (!root.value || chart) {
    return
  }

  chart = init(root.value)
  render(true)

  // 容器寬度改變時（RWD、旋轉螢幕）才重新計算尺寸與設定。
  // ResizeObserver 一開始觀察就會觸發一次，若此時重設選項會把剛開始的進場動畫中斷，所以寬度沒變就略過
  let lastWidth = root.value.clientWidth

  resizeObserver = new ResizeObserver(() => {
    const width = root.value?.clientWidth ?? lastWidth

    if (width === lastWidth) {
      return
    }

    lastWidth = width
    chart?.resize()
    render(false)
  })
  resizeObserver.observe(root.value)
}

onMounted(() => {
  if (!root.value) {
    return
  }

  if (!('IntersectionObserver' in window)) {
    start()
    return
  }

  // 捲到圖表區塊時才建立圖表，動畫才會在使用者看得到時播放
  visibilityObserver = new IntersectionObserver((entries) => {
    if (entries.some((entry) => entry.isIntersecting)) {
      visibilityObserver?.disconnect()
      start()
    }
  }, { threshold: 0.3 })
  visibilityObserver.observe(root.value)
})

onBeforeUnmount(() => {
  visibilityObserver?.disconnect()
  resizeObserver?.disconnect()
  chart?.dispose()
})
</script>

<template>
  <div ref="root" class="echart" role="img" :aria-label="label"></div>
</template>

<style scoped>
.echart {
  width: 100%;
  height: 22rem;
}

@media (min-width: 48rem) {
  .echart {
    height: 26rem;
  }
}
</style>
