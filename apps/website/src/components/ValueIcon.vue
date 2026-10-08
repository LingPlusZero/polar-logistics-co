<script setup lang="ts">
import type { ValueIconName } from '../data/about'

defineProps<{ name: ValueIconName }>()
</script>

<template>
  <svg
    width="44"
    height="44"
    viewBox="0 0 48 48"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linejoin="miter"
    aria-hidden="true"
  >
    <!-- 準時：時鐘 -->
    <template v-if="name === 'punctual'">
      <circle cx="24" cy="24" r="18" />
      <path d="M24 12V24L32 29" />
    </template>

    <!-- 精準：靶心與十字準線 -->
    <template v-else-if="name === 'precise'">
      <circle cx="24" cy="24" r="16" />
      <circle cx="24" cy="24" r="6" />
      <path d="M24 2V12M24 36V46M2 24H12M36 24H46" />
    </template>

    <!-- 驚喜滿滿：四芒星 -->
    <template v-else>
      <path d="M24 4L29 19L44 24L29 29L24 44L19 29L4 24L19 19Z" />
    </template>
  </svg>
</template>
