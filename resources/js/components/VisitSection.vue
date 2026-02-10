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
      <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{{ content }}</p>
      <ExplainButton @click="$emit('explain')" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ExplainButton from '@/components/ExplainButton.vue';

defineProps({
    title: { type: String, required: true },
    content: { type: String, default: '' },
});

defineEmits(['explain']);

const expanded = ref(false);
</script>
