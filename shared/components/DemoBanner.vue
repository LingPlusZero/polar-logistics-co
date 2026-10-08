<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'

const BANNER_HEIGHT = '2.25rem'

// 橫幅固定在頂部，需讓同樣固定的頁首往下讓出空間
onMounted(() => {
  document.documentElement.style.setProperty('--banner-height', BANNER_HEIGHT)
})

onBeforeUnmount(() => {
  document.documentElement.style.removeProperty('--banner-height')
})
</script>

<template>
  <div class="demo-banner">
    Demo 模式：資料僅存在你的瀏覽器，不會送出
  </div>
</template>

<style scoped>
.demo-banner {
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  z-index: 1000;
  height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 1rem;
  font-size: 0.8125rem;
  line-height: 1;
  color: var(--color-ice);
  background: var(--color-red);
}
</style>
