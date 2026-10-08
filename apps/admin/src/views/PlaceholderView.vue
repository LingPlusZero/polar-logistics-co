<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <section class="placeholder">
    <h1 class="placeholder__title">{{ route.meta.title }}</h1>
    <p class="placeholder__text">此功能建置中。</p>
  </section>
</template>

<style scoped>
.placeholder__title {
  font-size: 1.5rem;
  line-height: 1.4;
}

.placeholder__text {
  margin-top: 0.75rem;
  color: var(--color-text-muted);
}
</style>
