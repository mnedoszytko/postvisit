<template>
  <div class="space-y-2">
    <!-- Analysis steps -->
    <div class="flex items-center gap-2 text-xs">
      <div class="relative flex items-center justify-center w-5 h-5">
        <div class="absolute inset-0 bg-amber-400/20 rounded-full animate-ping" />
        <div class="relative w-3 h-3 bg-amber-500 rounded-full" />
      </div>
      <span class="text-amber-700 font-medium">
        {{ statusText || 'Preparing deep analysis...' }}
      </span>
    </div>

    <!-- Thinking preview (when thinking text is streaming) -->
    <div
      v-if="thinking"
      class="mt-2 bg-amber-50/50 border border-amber-200/50 rounded-lg px-3 py-2 max-h-24 overflow-hidden relative"
    >
      <div class="absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-amber-50/90 to-transparent pointer-events-none" />
      <pre class="text-[10px] text-amber-800/60 font-mono whitespace-pre-wrap break-words leading-relaxed">{{ thinkingPreview }}</pre>
    </div>

    <!-- Powered by label -->
    <p class="text-[9px] text-gray-400 flex items-center gap-1">
      <svg class="w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
      </svg>
      Powered by Claude Opus 4.6 — Extended Thinking
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  statusText: { type: String, default: '' },
  thinking: { type: String, default: '' },
  thinkingActive: { type: Boolean, default: false },
});

const thinkingPreview = computed(() => {
  if (!props.thinking) return '';
  const text = props.thinking;
  return text.length > 200 ? '...' + text.slice(-200) : text;
});
</script>
