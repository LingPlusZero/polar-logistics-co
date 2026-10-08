<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = withDefaults(defineProps<{
  target: number
  decimals?: number
  prefix?: string
  suffix?: string
  duration?: number
}>(), {
  decimals: 0,
  prefix: '',
  suffix: '',
  duration: 1600,
})

const root = ref<HTMLElement | null>(null)
const current = ref(0)

let observer: IntersectionObserver | null = null
let frameId = 0

const display = computed(() => current.value.toLocaleString('en-US', {
  minimumFractionDigits: props.decimals,
  maximumFractionDigits: props.decimals,
}))

// easeOutCubic：前段快、結尾慢，數字停下來比較自然
const ease = (t: number) => 1 - Math.pow(1 - t, 3)

const run = () => {
  const start = performance.now()

  const tick = (now: number) => {
    const progress = Math.min((now - start) / props.duration, 1)
    current.value = props.target * ease(progress)

    if (progress < 1) {
      frameId = requestAnimationFrame(tick)
    }
  }

  frameId = requestAnimationFrame(tick)
}

onMounted(() => {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

  if (reduceMotion || !('IntersectionObserver' in window)) {
    current.value = props.target
    return
  }

  // 第一次進入畫面才跳動，之後就停止觀察，不再重複執行
  observer = new IntersectionObserver((entries) => {
    if (entries.some((entry) => entry.isIntersecting)) {
      observer?.disconnect()
      run()
    }
  }, { threshold: 0.4 })

  if (root.value) {
    observer.observe(root.value)
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
  cancelAnimationFrame(frameId)
})
</script>

<template>
  <span ref="root">{{ prefix }}{{ display }}{{ suffix }}</span>
</template>
