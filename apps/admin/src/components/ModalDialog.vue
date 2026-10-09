<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'

const props = defineProps<{
  open: boolean
  title: string
}>()

const emit = defineEmits<{
  close: []
}>()

const dialog = ref<HTMLDialogElement | null>(null)

// 用原生 <dialog>：showModal 自帶遮罩、焦點鎖定與 Esc 關閉
watch(
  () => props.open,
  async (isOpen) => {
    await nextTick()

    if (isOpen && !dialog.value?.open) {
      dialog.value?.showModal()
    } else if (!isOpen && dialog.value?.open) {
      dialog.value.close()
    }
  },
  { immediate: true },
)

// Esc 觸發 cancel：交給父層決定是否關閉，避免 dialog 自己關掉而狀態沒同步
const onCancel = (event: Event) => {
  event.preventDefault()
  emit('close')
}
</script>

<template>
  <dialog ref="dialog" class="modal" :aria-label="title" @cancel="onCancel">
    <div v-if="open" class="modal__panel">
      <header class="modal__header">
        <h2 class="modal__title">{{ title }}</h2>
        <button type="button" class="modal__close" aria-label="關閉" @click="emit('close')">×</button>
      </header>

      <div class="modal__body">
        <slot />
      </div>

      <footer v-if="$slots.footer" class="modal__footer">
        <slot name="footer" />
      </footer>
    </div>
  </dialog>
</template>

<style scoped>
.modal {
  width: min(32rem, calc(100vw - 2rem));
  max-height: calc(100vh - 2rem);
  padding: 0;
  color: var(--color-navy);
  background: #fff;
  border: 0;
  border-top: 4px solid var(--color-navy);
  overflow: hidden;
}

.modal[open] {
  display: flex;
  flex-direction: column;
}

.modal::backdrop {
  background: rgb(11 37 69 / 0.55);
}

.modal__panel {
  display: flex;
  flex-direction: column;
  min-height: 0;
  max-height: calc(100vh - 2rem);
}

.modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--color-ice-dark);
}

.modal__title {
  font-size: 1.125rem;
  line-height: 1.4;
}

.modal__close {
  padding: 0 0.5rem;
  font: inherit;
  font-size: 1.5rem;
  line-height: 1.5;
  color: var(--color-text-muted);
  background: none;
  border: 0;
  cursor: pointer;
}

.modal__body {
  padding: 1.5rem;
  overflow-y: auto;
}

.modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-ice-dark);
}
</style>
