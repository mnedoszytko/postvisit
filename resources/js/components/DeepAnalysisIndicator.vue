<template>
  <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 space-y-2.5 animate-[pulse_2s_ease-in-out_infinite]">
    <!-- Main status row -->
    <div class="flex items-center gap-3">
      <!-- Pulsating brain icon -->
      <div class="relative flex items-center justify-center w-8 h-8 shrink-0">
        <div class="absolute inset-0 bg-amber-400/30 rounded-full animate-ping" />
        <div class="absolute inset-1 bg-amber-400/20 rounded-full animate-pulse" />
        <svg class="relative w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
        </svg>
      </div>
      <!-- Status text + timer -->
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-amber-800">{{ currentStatus }}</p>
        <p class="text-[10px] text-amber-600/70 font-mono mt-0.5">{{ elapsedFormatted }} elapsed</p>
      </div>
    </div>

    <!-- Progress bar (animated) -->
    <div class="w-full h-1 bg-amber-200/60 rounded-full overflow-hidden">
      <div
        class="h-full bg-amber-500 rounded-full transition-all duration-1000 ease-out"
        :style="{ width: progressWidth }"
      />
    </div>

    <!-- Powered by -->
    <p class="text-[9px] text-amber-600/50 flex items-center gap-1">
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
      </svg>
      Claude Opus 4.6 — Extended Thinking
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

// Progress bar: fills slowly over ~30s, never reaches 100%
const progressWidth = computed(() => {
  const pct = Math.min(90, (elapsed.value / 30) * 90);
  return `${pct}%`;
});

onMounted(() => {
  statusInterval = setInterval(() => {
    statusIndex.value++;
  }, 3000);

  timerInterval = setInterval(() => {
    elapsed.value++;
  }, 1000);
});

onUnmounted(() => {
  clearInterval(statusInterval);
  clearInterval(timerInterval);
});
</script>
