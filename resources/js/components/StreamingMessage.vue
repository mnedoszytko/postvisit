<template>
  <div class="prose prose-sm max-w-none" v-html="renderedHtml"></div>
  <span v-if="showCursor" class="animate-pulse">|</span>
</template>

<script setup>
import { computed } from 'vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

const props = defineProps({
    text: { type: String, default: '' },
    showCursor: { type: Boolean, default: true },
});

marked.setOptions({
    breaks: true,
    gfm: true,
});

const renderedHtml = computed(() => {
    const html = marked.parse(props.text || '');
    return DOMPurify.sanitize(html, { USE_PROFILES: { html: true } });
});
</script>
