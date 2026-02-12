<template>
  <div class="bg-gradient-to-br from-amber-50 to-orange-50/50 border border-amber-200/80 rounded-xl overflow-hidden">
    <!-- Header -->
    <div class="px-4 pt-3 pb-2">
      <div class="flex items-center gap-3">
        <!-- Animated sparkle icon -->
        <div class="relative flex items-center justify-center w-8 h-8 shrink-0">
          <div class="absolute inset-0 bg-amber-400/20 rounded-full animate-ping" style="animation-duration: 2s" />
          <svg class="relative w-4.5 h-4.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-amber-800">{{ fixedStatus }}</p>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[10px] text-amber-600/70 font-mono">{{ elapsedFormatted }}</span>
            <span v-if="thinkingTokenCount > 0" class="text-[10px] text-amber-500/50">&middot;</span>
            <span v-if="thinkingTokenCount > 0" class="text-[10px] text-amber-600/70 font-mono">
              {{ thinkingTokenCount.toLocaleString() }} tokens
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="px-4">
      <div class="w-full h-1 bg-amber-200/50 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-1000 ease-out"
          :class="thinkingActive ? 'bg-gradient-to-r from-amber-400 to-orange-400' : 'bg-amber-400/70'"
          :style="{ width: progressWidth }"
        />
      </div>
    </div>

    <!-- Thinking stream visualization -->
    <div v-if="thinking" class="px-4 pt-2">
      <div class="flex items-start gap-2 bg-amber-100/40 rounded-lg px-2.5 py-1.5">
        <span
          class="mt-1 w-1.5 h-1.5 rounded-full shrink-0 transition-colors duration-200"
          :class="thinkingActive ? 'bg-amber-500 animate-pulse' : 'bg-amber-400/40'"
        />
        <p class="text-[10px] text-amber-700/70 italic leading-relaxed line-clamp-2 min-h-[2lh]">
          {{ thinkingPreview }}
        </p>
      </div>
    </div>

    <!-- Footer: rotating status + powered by -->
    <div class="flex items-center justify-between px-4 py-2 mt-1">
      <p class="text-[10px] text-amber-600/60 italic">{{ currentRotating }}</p>
      <p class="text-[9px] text-amber-500/40 flex items-center gap-1 shrink-0">
        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
        </svg>
        Opus 4.6
      </p>
    </div>
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
  'Cross-referencing guidelines...',
  'Checking drug interactions...',
  'Palpating the evidence...',
  'Reviewing your chart...',
  'Consulting the literature...',
  'Taking vital signs of your question...',
  'Running differential diagnosis...',
  'Examining lab correlations...',
  'Prescribing an answer...',
  'Ordering a stat consult...',
  'Scrubbing in for analysis...',
  'Checking contraindications...',
  'Reviewing clinical trials...',
  'Titrating the response...',
  'Performing chart review...',
  'Triaging your concerns...',
  'Calibrating dosage data...',
  'Verifying against formulary...',
  'Interpreting pathology...',
];

const statusIndex = ref(0);
const elapsed = ref(0);
let statusInterval = null;
let timerInterval = null;

const fixedStatus = computed(() => {
  return props.statusText || 'Deep clinical reasoning...';
});

const currentRotating = computed(() => {
  return medicalStatuses[statusIndex.value % medicalStatuses.length];
});

const elapsedFormatted = computed(() => {
  const s = elapsed.value;
  if (s < 60) return `${s}s`;
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return `${m}m ${sec}s`;
});

// Progress bar: fills based on thinking tokens received + time, never reaches 100%
const progressWidth = computed(() => {
  const timePct = (elapsed.value / 30) * 50;
  const tokenPct = Math.min(40, (thinkingTokenCount.value / 2000) * 40);
  const pct = Math.min(90, timePct + tokenPct);
  return `${pct}%`;
});

// Thinking stream metrics
const thinkingTokenCount = computed(() => {
  if (!props.thinking) return 0;
  return Math.round(props.thinking.length / 4);
});

const thinkingPreview = computed(() => {
  if (!props.thinking) return '';
  const text = props.thinking.trim();
  if (text.length <= 80) return text;
  return '...' + text.slice(-77);
});

function scheduleNextStatus() {
  const delay = 3000 + Math.random() * 3000;
  statusInterval = setTimeout(() => {
    statusIndex.value++;
    scheduleNextStatus();
  }, delay);
}

onMounted(() => {
  statusIndex.value = Math.floor(Math.random() * medicalStatuses.length);
  scheduleNextStatus();

  timerInterval = setInterval(() => {
    elapsed.value++;
  }, 1000);
});

onUnmounted(() => {
  clearTimeout(statusInterval);
  clearInterval(timerInterval);
});
</script>
