<script setup lang="ts">
import type { DepartmentIconName } from '../data/departments'

defineProps<{ name: DepartmentIconName }>()
</script>

<template>
  <svg
    width="56"
    height="56"
    viewBox="0 0 48 48"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linejoin="miter"
    aria-hidden="true"
  >
    <!-- 禮物包裝：禮盒與緞帶 -->
    <template v-if="name === 'packaging'">
      <path d="M6 14H42V20H6ZM9 20V42H39V20M24 14V42" />
      <path d="M24 14L16 6M24 14L32 6" />
    </template>

    <!-- 外勤機動：屋頂與煙囪 -->
    <template v-else-if="name === 'field'">
      <path d="M4 24L24 8L44 24M10 20V42H38V20M32 14V8H37V18" />
      <path d="M20 42V30H28V42" />
    </template>

    <!-- 馴鹿管理：鹿角與頭部 -->
    <template v-else-if="name === 'reindeer'">
      <path d="M24 42L15 28L24 20L33 28Z" />
      <path d="M17 24L10 14V5M10 14L4 11M31 24L38 14V5M38 14L44 11" />
    </template>

    <!-- 乖寶寶稽核：清單與勾選 -->
    <template v-else-if="name === 'audit'">
      <path d="M10 8H38V42H10ZM18 4H30V12H18Z" />
      <path d="M16 26L22 32L33 20" />
    </template>

    <!-- 客戶體驗：對話框 -->
    <template v-else>
      <path d="M6 8H42V32H22L12 42V32H6Z" />
      <path d="M14 18H34M14 24H26" />
    </template>
  </svg>
</template>
