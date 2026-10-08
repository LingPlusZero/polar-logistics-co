<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

// 每 10,000 px² 的雪花數量，依面積換算，大小畫面看起來密度一致
const FLAKES_PER_AREA = 0.9
const MAX_FLAKES = 140

interface Flake {
  x: number
  y: number
  radius: number
  speed: number
  // 左右飄動的相位與幅度
  phase: number
  sway: number
  opacity: number
}

const canvas = ref<HTMLCanvasElement | null>(null)

let context: CanvasRenderingContext2D | null = null
let flakes: Flake[] = []
let width = 0
let height = 0
let frame = 0
let lastTime = 0
let observer: ResizeObserver | null = null

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)')

const createFlake = (startAnywhere: boolean): Flake => {
  const radius = 1 + Math.random() * 2.2

  return {
    x: Math.random() * width,
    y: startAnywhere ? Math.random() * height : -radius * 2,
    radius,
    // 大的雪花離得近，掉得比較快
    speed: 14 + radius * 9 + Math.random() * 10,
    phase: Math.random() * Math.PI * 2,
    sway: 8 + Math.random() * 18,
    opacity: 0.35 + Math.random() * 0.5,
  }
}

// 聖誕樹的尺寸（px）與距離右下角的邊距
const TREE_HEIGHT = 84
const TREE_MARGIN_RIGHT = 28
const TREE_MARGIN_BOTTOM = 14

// 吊飾位置：相對於樹寬、樹高（以樹底中心為原點）的比例，以及閃爍的相位
const ORNAMENTS = [
  { x: -0.05, y: 0.62, color: '#C8102E' },
  { x: 0.14, y: 0.45, color: '#F4F8FB' },
  { x: -0.12, y: 0.33, color: '#F4F8FB' },
  { x: 0.04, y: 0.2, color: '#C8102E' },
  { x: 0.2, y: 0.7, color: '#F4F8FB' },
]

// 單色線條、尖角，與 Logo 同一個風格；只有星星與兩顆吊飾用紅色強調
const drawTree = (time: number) => {
  if (!context || width < 320) {
    return
  }

  const treeWidth = TREE_HEIGHT * 0.72
  const centerX = width - TREE_MARGIN_RIGHT - treeWidth / 2
  const baseY = height - TREE_MARGIN_BOTTOM
  const trunkHeight = TREE_HEIGHT * 0.14
  const starSize = 6
  const foliageHeight = TREE_HEIGHT - trunkHeight - starSize
  const foliageTop = baseY - TREE_HEIGHT + starSize
  const tierHeight = foliageHeight * 0.44

  context.save()
  context.globalAlpha = 1
  context.lineWidth = 1.5
  context.lineJoin = 'miter'
  context.strokeStyle = '#F4F8FB'
  context.fillStyle = 'rgb(244 248 251 / 0.14)'

  // 樹幹
  context.fillRect(centerX - 3, baseY - trunkHeight, 6, trunkHeight)
  context.strokeRect(centerX - 3, baseY - trunkHeight, 6, trunkHeight)

  // 三層樹冠，越往下越寬；後畫的下層蓋住上層的底邊
  for (let tier = 0; tier < 3; tier++) {
    const top = foliageTop + tier * foliageHeight * 0.28
    const bottom = top + tierHeight
    const halfWidth = treeWidth * (0.28 + tier * 0.11)

    context.beginPath()
    context.moveTo(centerX, top)
    context.lineTo(centerX + halfWidth, bottom)
    context.lineTo(centerX - halfWidth, bottom)
    context.closePath()
    context.fill()
    context.stroke()
  }

  // 吊飾：緩慢閃爍
  ORNAMENTS.forEach((ornament, index) => {
    context!.globalAlpha = 0.55 + 0.45 * Math.sin(time / 700 + index * 1.3)
    context!.fillStyle = ornament.color
    context!.beginPath()
    context!.arc(centerX + ornament.x * TREE_HEIGHT, baseY - trunkHeight - ornament.y * foliageHeight * 0.9, 2, 0, Math.PI * 2)
    context!.fill()
  })

  // 頂端的四芒星
  context.globalAlpha = 1
  context.fillStyle = '#C8102E'
  context.beginPath()
  context.moveTo(centerX, foliageTop - starSize)
  context.lineTo(centerX + starSize * 0.4, foliageTop)
  context.lineTo(centerX, foliageTop + starSize * 0.5)
  context.lineTo(centerX - starSize * 0.4, foliageTop)
  context.closePath()
  context.fill()

  context.restore()
}

const draw = (time = performance.now()) => {
  if (!context) {
    return
  }

  context.clearRect(0, 0, width, height)

  // 先畫樹再畫雪，雪花會飄在樹的前面
  drawTree(time)

  for (const flake of flakes) {
    context.globalAlpha = flake.opacity
    context.beginPath()
    context.arc(flake.x + Math.sin(flake.phase) * flake.sway, flake.y, flake.radius, 0, Math.PI * 2)
    context.fillStyle = '#F4F8FB'
    context.fill()
  }
}

const tick = (time: number) => {
  // 以實際經過的時間推進，不同更新率的螢幕速度才會一致；分頁切走回來時限制單次最大位移
  const delta = Math.min((time - lastTime) / 1000, 0.1)
  lastTime = time

  for (const flake of flakes) {
    flake.y += flake.speed * delta
    flake.phase += delta * 0.8

    if (flake.y - flake.radius > height) {
      Object.assign(flake, createFlake(false))
    }
  }

  draw(time)
  frame = requestAnimationFrame(tick)
}

const stop = () => cancelAnimationFrame(frame)

const start = () => {
  stop()

  // 減少動態效果：只畫一張靜態的雪景
  if (prefersReducedMotion.matches) {
    draw()
    return
  }

  lastTime = performance.now()
  frame = requestAnimationFrame(tick)
}

const resize = () => {
  const element = canvas.value

  if (!element || !context) {
    return
  }

  const ratio = window.devicePixelRatio || 1
  width = element.clientWidth
  height = element.clientHeight

  // 依裝置像素比放大畫布，高解析螢幕上才不會模糊
  element.width = width * ratio
  element.height = height * ratio
  context.setTransform(ratio, 0, 0, ratio, 0, 0)

  const count = Math.min(MAX_FLAKES, Math.round((width * height) / 10000 * FLAKES_PER_AREA))
  flakes = Array.from({ length: count }, () => createFlake(true))

  start()
}

// 分頁在背景時不必繼續畫，省電
const onVisibilityChange = () => {
  if (document.hidden) {
    stop()
  } else {
    start()
  }
}

onMounted(() => {
  context = canvas.value?.getContext('2d') ?? null

  if (!canvas.value || !context) {
    return
  }

  observer = new ResizeObserver(resize)
  observer.observe(canvas.value)
  document.addEventListener('visibilitychange', onVisibilityChange)
  prefersReducedMotion.addEventListener('change', start)
})

onBeforeUnmount(() => {
  stop()
  observer?.disconnect()
  document.removeEventListener('visibilitychange', onVisibilityChange)
  prefersReducedMotion.removeEventListener('change', start)
})
</script>

<template>
  <canvas ref="canvas" class="snow" aria-hidden="true"></canvas>
</template>

<style scoped>
.snow {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  /* 雪花只是背景，不能擋住下方內容的點擊 */
  pointer-events: none;
}
</style>
