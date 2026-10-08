<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { filterMenu, isGroup } from '../config/menu'
import { useAuth } from '../composables/useAuth'

const route = useRoute()
const { can } = useAuth()

// 權限在登入時就固定，選單依此計算一次即可
const items = computed(() => filterMenu(can))

// 手風琴：同時只展開一組
const openGroup = ref<string | null>(null)

const toggle = (label: string) => {
  openGroup.value = openGroup.value === label ? null : label
}

// 進入群組內的頁面（含直接輸入網址、重新整理）時，自動展開該群組
watch(
  () => [route.path, items.value] as const,
  ([path]) => {
    const group = items.value.find(
      (item) => isGroup(item) && item.children.some((child) => child.path === path),
    )

    if (group) {
      openGroup.value = group.label
    }
  },
  { immediate: true },
)

const hasActiveChild = (label: string) => {
  const group = items.value.find((item) => item.label === label)
  return !!group && isGroup(group) && group.children.some((child) => child.path === route.path)
}
</script>

<template>
  <nav class="menu" aria-label="主選單">
    <RouterLink to="/" class="menu__link" exact-active-class="menu__link--active">首頁</RouterLink>

    <template v-for="item in items" :key="item.label">
      <div v-if="isGroup(item)" class="menu__group">
        <button
          type="button"
          class="menu__link menu__toggle"
          :class="{ 'menu__toggle--current': openGroup !== item.label && hasActiveChild(item.label) }"
          :aria-expanded="openGroup === item.label"
          :aria-controls="`menu-${item.label}`"
          @click="toggle(item.label)"
        >
          <span>{{ item.label }}</span>
          <span class="menu__chevron" :class="{ 'menu__chevron--open': openGroup === item.label }" aria-hidden="true"></span>
        </button>

        <!-- 用 grid 列高 0fr → 1fr 做展開動畫；收合時 inert，避免 Tab 鍵停在看不見的連結上 -->
        <div
          :id="`menu-${item.label}`"
          class="menu__panel"
          :class="{ 'menu__panel--open': openGroup === item.label }"
          :inert="openGroup !== item.label"
        >
          <div class="menu__panel-inner">
            <RouterLink
              v-for="child in item.children"
              :key="child.path"
              :to="child.path"
              class="menu__link menu__link--child"
              exact-active-class="menu__link--active"
            >
              {{ child.label }}
            </RouterLink>
          </div>
        </div>
      </div>

      <RouterLink
        v-else
        :to="item.path"
        class="menu__link"
        exact-active-class="menu__link--active"
      >
        {{ item.label }}
      </RouterLink>
    </template>
  </nav>
</template>

<style scoped>
.menu {
  display: flex;
  flex-direction: column;
}

/* 首頁、群組標題、子選單共用同一套外觀 */
.menu__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.625rem 1.5rem;
  font: inherit;
  font-size: 0.9375rem;
  text-align: left;
  text-decoration: none;
  color: var(--color-ice);
  background: none;
  border: 0;
  border-left: 3px solid transparent;
  cursor: pointer;
}

.menu__link--child {
  padding-left: 2.5rem;
}

.menu__link:hover {
  background: var(--color-navy-light);
}

/* 目前頁面用紅色左線標示，紅色只當強調色 */
.menu__link--active {
  background: var(--color-navy-light);
  border-left-color: var(--color-red);
}

/* 群組收合時，若目前頁面在群組內，標題也要有提示 */
.menu__toggle--current {
  border-left-color: var(--color-red);
}

.menu__link:focus-visible {
  outline: 2px solid var(--color-ice);
  outline-offset: -2px;
}

/* 以邊框旋轉出的箭頭，不用圖片 */
.menu__chevron {
  width: 0.5rem;
  height: 0.5rem;
  margin-right: 0.25rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 0.2s ease;
}

.menu__chevron--open {
  transform: rotate(-135deg);
}

.menu__panel {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.2s ease;
}

.menu__panel--open {
  grid-template-rows: 1fr;
}

.menu__panel-inner {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
  .menu__chevron,
  .menu__panel {
    transition: none;
  }
}
</style>
