<template>
  <div class="prose prose-sm max-w-none" v-html="renderedHtml"></div>
  <span v-if="showCursor" class="inline-block w-1.5 h-3.5 bg-gray-400 rounded-sm animate-pulse align-text-bottom ml-0.5"></span>
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
