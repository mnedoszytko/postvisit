<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-sm">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="toastClass(toast.type)"
        class="rounded-lg px-4 py-3 shadow-lg flex items-start gap-3 cursor-pointer"
        @click="store.remove(toast.id)"
      >
        <span class="text-lg leading-none mt-0.5">{{ icon(toast.type) }}</span>
        <p class="text-sm leading-snug flex-1">{{ toast.message }}</p>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useToastStore } from '@/stores/toast';

const store = useToastStore();
const toasts = computed(() => store.toasts);

function toastClass(type) {
    return {
        error: 'bg-red-50 text-red-800 border border-red-200',
        success: 'bg-green-50 text-green-800 border border-green-200',
        warning: 'bg-amber-50 text-amber-800 border border-amber-200',
        info: 'bg-blue-50 text-blue-800 border border-blue-200',
    }[type] || 'bg-gray-50 text-gray-800 border border-gray-200';
}

function icon(type) {
    return {
        error: '\u2716',
        success: '\u2714',
        warning: '\u26A0',
        info: '\u2139',
    }[type] || '\u2139';
}
</script>

<style scoped>
.toast-enter-active {
    transition: all 0.3s ease-out;
}
.toast-leave-active {
    transition: all 0.2s ease-in;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
