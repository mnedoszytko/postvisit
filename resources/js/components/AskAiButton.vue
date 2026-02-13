<template>
  <span
    role="button"
    tabindex="0"
    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors cursor-pointer"
    title="Ask about this section"
    @click.stop="$emit('ask')"
    @keydown.enter.stop="$emit('ask')"
  >
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
    </svg>
    Ask
  </span>
</template>

<script setup>
defineEmits(['ask']);
</script>
