<script setup lang="ts">
// 指向北方的箭頭，兩側分岔與側枝構成馴鹿角；單色線條、尖角、不加外框
withDefaults(defineProps<{ size?: number }>(), { size: 32 })
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 48 48"
    fill="none"
    stroke="currentColor"
    stroke-width="2.5"
    stroke-linecap="butt"
    stroke-linejoin="miter"
    role="img"
    aria-label="極地物流"
  >
    <!-- 主幹與箭頭 -->
    <path d="M24 44V4M17 11L24 4L31 11" />
    <!-- 左角：分岔與側枝 -->
    <path d="M24 30L13 21V9M13 21L6 17M13 15L8 11" />
    <!-- 右角 -->
    <path d="M24 30L35 21V9M35 21L42 17M35 15L40 11" />
  </svg>
</template>
