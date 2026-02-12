<template>
  <div class="space-y-1.5">
    <!-- Status line with timer (Claude Code style) -->
    <div class="flex items-center gap-2 text-xs">
      <span class="relative flex h-2 w-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75" />
        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500" />
      </span>
      <span class="text-amber-700 font-medium">{{ currentStatus }}</span>
      <span class="text-amber-500/60 font-mono text-[10px]">({{ elapsedFormatted }})</span>
    </div>

    <!-- Powered by label -->
    <p class="text-[9px] text-gray-400 flex items-center gap-1 pl-4">
      <svg class="w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
      </svg>
      Powered by Claude Opus 4.6 — Extended Thinking
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  statusText: { type: String, default: '' },
  thinking: { type: String, default: '' },
  thinkingActive: { type: Boolean, default: false },
});

// Rotating medical-themed statuses
const medicalStatuses = [
  'Auscultating the data...',
  'Cross-referencing clinical guidelines...',
  'Checking drug interactions...',
  'Palpating the evidence...',
  'Reviewing your chart...',
  'Consulting the literature...',
  'Taking vital signs of your question...',
  'Running differential diagnosis...',
  'Examining lab correlations...',
  'Prescribing an answer...',
];

const statusIndex = ref(0);
const elapsed = ref(0);
let statusInterval = null;
let timerInterval = null;

const currentStatus = computed(() => {
  // If backend sends a specific status, use it
  if (props.statusText) return props.statusText;
  return medicalStatuses[statusIndex.value % medicalStatuses.length];
});

const elapsedFormatted = computed(() => {
  const s = elapsed.value;
  if (s < 60) return `${s}s`;
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return `${m}m ${sec}s`;
});

onMounted(() => {
  // Rotate statuses every 3 seconds
  statusInterval = setInterval(() => {
    statusIndex.value++;
  }, 3000);

  // Count elapsed time
  timerInterval = setInterval(() => {
    elapsed.value++;
  }, 1000);
});

onUnmounted(() => {
  clearInterval(statusInterval);
  clearInterval(timerInterval);
});
</script>
