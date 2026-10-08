<script setup lang="ts">
import DemoBanner from '@shared/components/DemoBanner.vue'
import { isDemoMode } from '@shared/api'
import SiteFooter from './components/SiteFooter.vue'
import SiteHeader from './components/SiteHeader.vue'
</script>

<template>
  <DemoBanner v-if="isDemoMode" />
  <SiteHeader />
  <RouterView />
  <SiteFooter />
</template>
