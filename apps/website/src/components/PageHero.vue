<script setup lang="ts">
import HeroBackdrop from './HeroBackdrop.vue'

defineProps<{
  eyebrow: string
  title: string
  subtitle?: string
}>()
</script>

<template>
  <section class="page-hero">
    <HeroBackdrop />
    <div class="container page-hero__content">
      <p class="page-hero__eyebrow">{{ eyebrow }}</p>
      <h1 class="page-hero__title">{{ title }}</h1>
      <p v-if="subtitle" class="page-hero__subtitle">{{ subtitle }}</p>
    </div>
  </section>
</template>

<style scoped>
.page-hero {
  position: relative;
  overflow: hidden;
  padding: 2.75rem 0;
  color: var(--color-ice);
  background: var(--color-navy);
  border-bottom: 2px solid var(--color-red);
}

.page-hero__content {
  position: relative;
}

.page-hero__eyebrow {
  margin-bottom: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--color-ice-dark);
}

.page-hero__title {
  font-size: 1.75rem;
  line-height: 1.4;
}

.page-hero__subtitle {
  margin-top: 0.75rem;
  font-size: 1.0625rem;
  color: var(--color-ice-dark);
}

@media (min-width: 48rem) {
  .page-hero {
    padding: 3.75rem 0;
  }

  .page-hero__title {
    font-size: 2.5rem;
  }
}
</style>
