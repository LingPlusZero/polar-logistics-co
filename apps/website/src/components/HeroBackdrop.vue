<script setup lang="ts">
// 北極點投影：同心緯線 + 放射經線，圓心靠右；對應總部「北緯 90 度 0 分」的設定
// 內頁標題區較矮，放大的符號會壓到標題，只有首頁 banner 顯示
withDefaults(defineProps<{ mark?: boolean }>(), { mark: false })

const CENTER = { x: 640, y: 300 }
const PARALLELS = [80, 160, 240, 320, 400, 480, 560]
const MERIDIAN_COUNT = 12

// 經線由圓心向外，每 30 度一條，長度足以延伸出畫面
const meridians = Array.from({ length: MERIDIAN_COUNT }, (_, i) => {
  const angle = (Math.PI * 2 * i) / MERIDIAN_COUNT
  return {
    x: CENTER.x + Math.cos(angle) * 700,
    y: CENTER.y + Math.sin(angle) * 700,
  }
})
</script>

<template>
  <svg
    class="hero-backdrop"
    viewBox="0 0 800 600"
    preserveAspectRatio="xMaxYMid slice"
    fill="none"
    aria-hidden="true"
  >
    <g class="hero-backdrop__lines">
      <circle v-for="radius in PARALLELS" :key="radius" :cx="CENTER.x" :cy="CENTER.y" :r="radius" />
      <line v-for="(end, i) in meridians" :key="i" :x1="CENTER.x" :y1="CENTER.y" :x2="end.x" :y2="end.y" />
    </g>

    <!-- 圓心放大的品牌符號，作為浮水印 -->
    <g v-if="mark"
      class="hero-backdrop__mark"
      :transform="`translate(${CENTER.x - 120} ${CENTER.y - 120}) scale(5)`"
    >
      <path d="M24 44V4M17 11L24 4L31 11" />
      <path d="M24 30L13 21V9M13 21L6 17M13 15L8 11" />
      <path d="M24 30L35 21V9M35 21L42 17M35 15L40 11" />
    </g>
  </svg>
</template>

<style scoped>
.hero-backdrop {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.hero-backdrop__lines {
  stroke: #fff;
  stroke-opacity: 0.08;
  stroke-width: 1;
  vector-effect: non-scaling-stroke;
}

.hero-backdrop__lines circle,
.hero-backdrop__lines line {
  vector-effect: non-scaling-stroke;
}

.hero-backdrop__mark {
  stroke: #fff;
  stroke-opacity: 0.07;
  stroke-width: 1.4;
  stroke-linejoin: miter;
}
</style>
