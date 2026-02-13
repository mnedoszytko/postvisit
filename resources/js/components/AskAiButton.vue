<template>
  <span
    role="button"
    tabindex="0"
    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors cursor-pointer"
    title="Ask about this section"
    @click.stop="$emit('ask')"
    @keydown.enter.stop="$emit('ask')"
  >
    <img src="/images/logo-icon.png" alt="" class="w-3.5 h-3.5" />
    Ask
  </span>
</template>

<script setup>
defineEmits(['ask']);
</script>
