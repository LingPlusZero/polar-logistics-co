<script setup lang="ts">
import BrandLogo from '@shared/components/BrandLogo.vue'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const NAV_LINKS = [
  { to: '/investors', label: '投資人關係' },
  { to: '/careers', label: '人才招募' },
  { to: '/contact', label: '聯繫我們' },
]

const route = useRoute()
const isOpen = ref(false)

// 換頁後收起漢堡選單
watch(() => route.path, () => {
  isOpen.value = false
})

const closeOnEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    isOpen.value = false
  }
}

onMounted(() => window.addEventListener('keydown', closeOnEscape))
onBeforeUnmount(() => window.removeEventListener('keydown', closeOnEscape))
</script>

<template>
  <header class="site-header">
    <div class="site-header__inner container">
      <RouterLink to="/" class="site-header__brand">
        <BrandLogo :size="32" />
        <span class="site-header__name">極地物流</span>
      </RouterLink>

      <button
        type="button"
        class="site-header__toggle"
        :aria-expanded="isOpen"
        aria-controls="site-nav"
        aria-label="選單"
        @click="isOpen = !isOpen"
      >
        <span class="site-header__bar" :class="{ 'site-header__bar--open': isOpen }"></span>
      </button>

      <nav id="site-nav" class="site-header__nav" :class="{ 'site-header__nav--open': isOpen }">
        <RouterLink v-for="link in NAV_LINKS" :key="link.to" :to="link.to" class="site-header__link">
          {{ link.label }}
        </RouterLink>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.site-header {
  position: fixed;
  top: var(--banner-height);
  right: 0;
  left: 0;
  z-index: 100;
  height: var(--header-height);
  color: var(--color-ice);
  background: var(--color-navy);
}

.site-header__inner {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.site-header__brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
}

.site-header__name {
  font-size: 1.125rem;
  font-weight: 700;
  letter-spacing: 0.1em;
}

/* 小螢幕：漢堡按鈕 + 展開式選單 */
.site-header__toggle {
  position: relative;
  width: 2.5rem;
  height: 2.5rem;
  padding: 0;
  color: inherit;
  background: none;
  border: 0;
  cursor: pointer;
}

.site-header__bar,
.site-header__bar::before,
.site-header__bar::after {
  position: absolute;
  left: 0.5rem;
  width: 1.5rem;
  height: 2px;
  background: currentColor;
  transition: transform 0.2s, background 0.2s;
}

.site-header__bar {
  top: calc(50% - 1px);
}

.site-header__bar::before,
.site-header__bar::after {
  content: "";
  left: 0;
}

.site-header__bar::before {
  top: -0.4375rem;
}

.site-header__bar::after {
  top: 0.4375rem;
}

/* 展開時變成 X：中間線消失，上下兩條旋轉 */
.site-header__bar--open {
  background: transparent;
}

.site-header__bar--open::before {
  transform: translateY(0.4375rem) rotate(45deg);
}

.site-header__bar--open::after {
  transform: translateY(-0.4375rem) rotate(-45deg);
}

.site-header__nav {
  position: fixed;
  top: calc(var(--banner-height) + var(--header-height));
  right: 0;
  left: 0;
  display: none;
  flex-direction: column;
  padding: 0.5rem 1.25rem 1rem;
  background: var(--color-navy);
  border-top: 1px solid var(--color-navy-light);
}

.site-header__nav--open {
  display: flex;
}

.site-header__link {
  padding: 0.875rem 0;
  text-decoration: none;
  border-bottom: 1px solid var(--color-navy-light);
}

.site-header__link.router-link-active {
  padding-left: 0.75rem;
  box-shadow: inset 0.1875rem 0 0 var(--color-red);
}

@media (min-width: 48rem) {
  .site-header__toggle {
    display: none;
  }

  .site-header__nav {
    position: static;
    display: flex;
    flex-direction: row;
    gap: 2rem;
    padding: 0;
    border-top: 0;
  }

  .site-header__link {
    padding: 0.5rem 0;
    border-bottom: 2px solid transparent;
  }

  .site-header__link:hover {
    border-bottom-color: var(--color-ice-dark);
  }

  .site-header__link.router-link-active {
    padding-left: 0;
    box-shadow: none;
    border-bottom-color: var(--color-red);
  }
}
</style>
