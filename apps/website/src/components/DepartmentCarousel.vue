<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { DEPARTMENTS } from '../data/departments'
import DepartmentIcon from './DepartmentIcon.vue'

const AUTOPLAY_INTERVAL = 6000

const index = ref(0)
const isPaused = ref(false)

let timer = 0

const go = (target: number) => {
  // 頭尾相接，往前/往後都能循環
  index.value = (target + DEPARTMENTS.length) % DEPARTMENTS.length
}

onMounted(() => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return
  }

  timer = window.setInterval(() => {
    if (!isPaused.value) {
      go(index.value + 1)
    }
  }, AUTOPLAY_INTERVAL)
})

onBeforeUnmount(() => window.clearInterval(timer))
</script>

<template>
  <div
    class="carousel"
    role="region"
    aria-roledescription="輪播"
    aria-label="五大業務"
    @mouseenter="isPaused = true"
    @mouseleave="isPaused = false"
    @focusin="isPaused = true"
    @focusout="isPaused = false"
  >
    <div class="carousel__viewport">
      <div class="carousel__track" :style="{ transform: `translateX(-${index * 100}%)` }">
        <section
          v-for="(department, i) in DEPARTMENTS"
          :key="department.key"
          class="carousel__slide"
          :style="{ background: department.background }"
          :aria-hidden="i !== index"
        >
          <DepartmentIcon :name="department.key" />
          <h3 class="carousel__name">{{ department.name }}</h3>
          <p class="carousel__description">{{ department.description }}</p>
        </section>
      </div>
    </div>

    <div class="carousel__controls">
      <button type="button" class="carousel__arrow" aria-label="上一個業務" @click="go(index - 1)">‹</button>
      <div class="carousel__dots">
        <button
          v-for="(department, i) in DEPARTMENTS"
          :key="department.key"
          type="button"
          class="carousel__dot"
          :class="{ 'carousel__dot--active': i === index }"
          :aria-label="department.name"
          :aria-current="i === index"
          @click="go(i)"
        ></button>
      </div>
      <button type="button" class="carousel__arrow" aria-label="下一個業務" @click="go(index + 1)">›</button>
    </div>
  </div>
</template>

<style scoped>
.carousel__viewport {
  overflow: hidden;
}

.carousel__track {
  display: flex;
  transition: transform 0.5s ease;
}

.carousel__slide {
  flex: 0 0 100%;
  min-height: 18rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
  color: #fff;
}

.carousel__name {
  margin-top: 1.25rem;
  font-size: 1.5rem;
}

.carousel__description {
  max-width: 34rem;
  margin-top: 0.75rem;
  color: rgba(255, 255, 255, 0.85);
}

.carousel__controls {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.25rem;
  margin-top: 1.25rem;
}

.carousel__arrow {
  width: 2.5rem;
  height: 2.5rem;
  font-size: 1.5rem;
  line-height: 1;
  color: var(--color-navy);
  background: none;
  border: 1px solid var(--color-navy);
  border-radius: 50%;
  cursor: pointer;
}

.carousel__arrow:hover {
  color: var(--color-ice);
  background: var(--color-navy);
}

.carousel__dots {
  display: flex;
  gap: 0.625rem;
}

.carousel__dot {
  width: 0.625rem;
  height: 0.625rem;
  padding: 0;
  background: var(--color-ice-dark);
  border: 0;
  border-radius: 50%;
  cursor: pointer;
}

.carousel__dot--active {
  background: var(--color-red);
}

@media (prefers-reduced-motion: reduce) {
  .carousel__track {
    transition: none;
  }
}

@media (min-width: 48rem) {
  .carousel__slide {
    min-height: 22rem;
  }

  .carousel__name {
    font-size: 1.875rem;
  }
}
</style>
