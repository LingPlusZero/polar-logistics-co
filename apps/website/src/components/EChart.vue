<script setup lang="ts">
import { LineChart } from 'echarts/charts'
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components'
import { init, use, type ECharts, type EChartsCoreOption } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

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

const render = () => {
  if (chart && root.value) {
    chart.setOption(props.buildOption(root.value.clientWidth), true)
  }
}

onMounted(() => {
  if (!root.value) {
    return
  }

  chart = init(root.value)
  render()

  // 容器寬度改變時（RWD、旋轉螢幕）重新計算尺寸與設定
  resizeObserver = new ResizeObserver(() => {
    chart?.resize()
    render()
  })
  resizeObserver.observe(root.value)
})

// 資料更新時重畫
watch(() => props.buildOption, render)

onBeforeUnmount(() => {
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
