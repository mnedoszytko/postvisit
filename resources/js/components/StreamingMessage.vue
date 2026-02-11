<template>
  <div class="prose prose-sm max-w-none" v-html="renderedHtml"></div>
  <span v-if="showCursor" class="animate-pulse">|</span>
</template>

<script setup>
import { computed } from 'vue';
import { marked } from 'marked';

const props = defineProps({
    text: { type: String, default: '' },
    showCursor: { type: Boolean, default: true },
});

marked.setOptions({
    breaks: true,
    gfm: true,
});

const renderedHtml = computed(() => marked.parse(props.text || ''));
</script>
