<script setup lang="ts">
defineProps<{ name: 'phone' | 'location' | 'link' }>()
</script>

<template>
  <svg
    width="28"
    height="28"
    viewBox="0 0 48 48"
    fill="none"
    stroke="currentColor"
    stroke-width="2.5"
    stroke-linejoin="miter"
    aria-hidden="true"
  >
    <!-- 客服專線：話筒 -->
    <template v-if="name === 'phone'">
      <path d="M10 6H18L22 16L16 20C18 26 22 30 28 32L32 26L42 30V38H36C20 38 8 26 8 10V6" />
    </template>

    <!-- 總部：定位點 -->
    <template v-else-if="name === 'location'">
      <path d="M24 44L10 24C6 16 10 4 24 4C38 4 42 16 38 24Z" />
      <circle cx="24" cy="19" r="5" />
    </template>

    <!-- 其他入口：箭頭連結 -->
    <template v-else>
      <path d="M8 24H38M28 14L38 24L28 34" />
    </template>
  </svg>
</template>
