<template>
  <p class="whitespace-pre-wrap">{{ text }}<span v-if="showCursor" class="animate-pulse">|</span></p>
</template>

<script setup>
defineProps({
    text: { type: String, default: '' },
    showCursor: { type: Boolean, default: true },
});
</script>
