<script setup lang="ts">
import BrandLogo from '@shared/components/BrandLogo.vue'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SideMenu from '../components/SideMenu.vue'
import { useAuth } from '../composables/useAuth'

const route = useRoute()
const router = useRouter()
const { profile, can, logout } = useAuth()

// 窄螢幕時選單改為可收合
const isOpen = ref(false)

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

const signOut = async () => {
  await logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <div class="layout">
    <header class="topbar">
      <button
        type="button"
        class="topbar__toggle"
        :aria-expanded="isOpen"
        aria-controls="sidebar"
        @click="isOpen = !isOpen"
      >
        選單
      </button>
      <span class="topbar__name">精靈管理系統</span>
    </header>

    <aside id="sidebar" class="sidebar" :class="{ 'sidebar--open': isOpen }">
      <RouterLink to="/" class="sidebar__brand">
        <BrandLogo :size="32" />
        <span>
          <strong class="sidebar__company">極地物流</strong>
          <small class="sidebar__system">精靈管理系統</small>
        </span>
      </RouterLink>

      <div class="sidebar__menu">
        <SideMenu />
      </div>

      <div class="sidebar__user">
        <p class="sidebar__user-name">{{ profile?.name }}</p>
        <p class="sidebar__user-meta">{{ profile?.department }}　{{ profile?.rank }}</p>
        <div class="sidebar__actions">
          <RouterLink v-if="can('password.change')" to="/password" class="sidebar__action">
            修改密碼
          </RouterLink>
          <button type="button" class="sidebar__action" @click="signOut">登出</button>
        </div>
      </div>
    </aside>

    <div v-if="isOpen" class="overlay" @click="isOpen = false"></div>

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
  padding-top: calc(var(--banner-height) + var(--header-height));
}

/* 窄螢幕的頂列；寬螢幕改由固定側欄取代 */
.topbar {
  position: fixed;
  top: var(--banner-height);
  right: 0;
  left: 0;
  z-index: 20;
  height: var(--header-height);
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0 1rem;
  color: var(--color-ice);
  background: var(--color-navy);
}

.topbar__toggle {
  padding: 0.375rem 0.75rem;
  font: inherit;
  font-size: 0.875rem;
  color: inherit;
  background: transparent;
  border: 1px solid var(--color-ice-dark);
  cursor: pointer;
}

.topbar__name {
  font-weight: 700;
}

.sidebar {
  position: fixed;
  top: calc(var(--banner-height) + var(--header-height));
  bottom: 0;
  left: 0;
  z-index: 30;
  width: 16rem;
  max-width: 85vw;
  display: flex;
  flex-direction: column;
  color: var(--color-ice);
  background: var(--color-navy);
  transform: translateX(-100%);
  transition: transform 0.2s ease;
}

.sidebar--open {
  transform: none;
}

.sidebar__brand {
  display: none;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  text-decoration: none;
  color: var(--color-ice);
  border-bottom: 1px solid var(--color-navy-light);
}

.sidebar__company {
  display: block;
  line-height: 1.3;
}

.sidebar__system {
  display: block;
  font-size: 0.75rem;
  color: var(--color-ice-dark);
}

.sidebar__menu {
  flex: 1;
  padding: 0.75rem 0;
  overflow-y: auto;
  /* 選單過長時仍可用滾輪或觸控捲動，只是不顯示捲軸 */
  scrollbar-width: none;
}

.sidebar__menu::-webkit-scrollbar {
  display: none;
}

.sidebar__user {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-navy-light);
}

.sidebar__user-name {
  font-weight: 700;
}

.sidebar__user-meta {
  font-size: 0.8125rem;
  color: var(--color-ice-dark);
}

.sidebar__actions {
  display: flex;
  gap: 1rem;
  margin-top: 0.75rem;
}

.sidebar__action {
  padding: 0;
  font: inherit;
  font-size: 0.875rem;
  color: var(--color-ice);
  text-decoration: underline;
  background: none;
  border: 0;
  cursor: pointer;
}

.overlay {
  position: fixed;
  inset: calc(var(--banner-height) + var(--header-height)) 0 0;
  z-index: 25;
  background: rgb(11 37 69 / 0.5);
}

.content {
  padding: 1.5rem 1rem;
}

@media (min-width: 48em) {
  .layout {
    padding-top: var(--banner-height);
  }

  .topbar,
  .overlay {
    display: none;
  }

  .sidebar {
    top: var(--banner-height);
    max-width: none;
    transform: none;
    transition: none;
  }

  .sidebar__brand {
    display: flex;
  }

  .content {
    margin-left: 16rem;
    padding: 2rem;
  }
}
</style>
