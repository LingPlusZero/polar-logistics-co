<script setup lang="ts">
import DemoBanner from '@shared/components/DemoBanner.vue'
import { isDemoMode } from '@shared/api'
</script>

<template>
  <DemoBanner v-if="isDemoMode" />
  <RouterView />
</template>
