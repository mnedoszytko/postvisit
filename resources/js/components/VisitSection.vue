<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <button
      class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors"
      @click="expanded = !expanded"
    >
      <h3 class="font-semibold text-gray-800">{{ title }}</h3>
      <span class="text-gray-400 text-sm">{{ expanded ? 'Collapse' : 'Expand' }}</span>
    </button>

    <div v-if="expanded" class="px-4 pb-4 space-y-3">
      <HighlightedText
        v-if="terms?.length"
        :content="content"
        :terms="terms"
        :section-key="sectionKey"
        @term-click="(payload) => $emit('term-click', payload)"
      />
      <div v-else class="prose prose-sm max-w-none text-gray-700" v-html="renderContent(content)" />
      <ExplainButton @click="$emit('explain')" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { marked } from 'marked';
import ExplainButton from '@/components/ExplainButton.vue';
import HighlightedText from '@/components/HighlightedText.vue';

marked.setOptions({ breaks: true, gfm: true });

function renderContent(text) {
    if (!text) return '';
    return marked.parse(text);
}

defineProps({
    title: { type: String, required: true },
    content: { type: String, default: '' },
    terms: { type: Array, default: () => [] },
    sectionKey: { type: String, default: '' },
});

defineEmits(['explain', 'term-click']);

const expanded = ref(false);
</script>
