<script setup lang="ts">
import ModalDialog from './ModalDialog.vue'

// 取代 alert / confirm 的二次確認視窗（docs/admin.md）
withDefaults(
  defineProps<{
    open: boolean
    title: string
    message: string
    confirmLabel?: string
    isBusy?: boolean
    errorMessage?: string
  }>(),
  { confirmLabel: '確認', isBusy: false, errorMessage: '' },
)

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <ModalDialog :open="open" :title="title" @close="emit('cancel')">
    <p class="confirm__message">{{ message }}</p>
    <p v-if="errorMessage" class="field__error" role="alert">{{ errorMessage }}</p>

    <template #footer>
      <button type="button" class="button" :disabled="isBusy" @click="emit('cancel')">取消</button>
      <button
        type="button"
        class="button button--danger"
        :disabled="isBusy"
        @click="emit('confirm')"
      >
        {{ confirmLabel }}
      </button>
    </template>
  </ModalDialog>
</template>

<style scoped>
.confirm__message {
  white-space: pre-line;
}
</style>
