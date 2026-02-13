<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-emerald-50 to-white px-4">
    <div class="text-center space-y-8 max-w-md w-full">
      <LoadingParticles />

      <div class="space-y-2">
        <h1 class="text-2xl font-semibold text-gray-800">
          {{ insufficientContent ? 'Not enough content' : failed ? 'Processing failed' : 'Analyzing your visit...' }}
        </h1>
        <p class="text-gray-500 text-sm">{{ currentStep.label }}</p>
      </div>

      <!-- Progress bar with time estimate -->
      <div v-if="!failed && !insufficientContent && estimatedTotalSeconds > 0" class="space-y-2 px-2">
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-emerald-500 rounded-full transition-all duration-1000 ease-linear"
            :style="{ width: progressPercent + '%' }"
          />
        </div>
        <div class="flex justify-between text-xs text-gray-400">
          <span>{{ elapsedFormatted }}</span>
          <span v-if="remainingText">{{ remainingText }}</span>
        </div>
      </div>

      <!-- Elapsed only (no duration info) -->
      <p v-else-if="!failed && !insufficientContent" class="text-gray-400 text-xs">
        {{ elapsedFormatted }} elapsed
      </p>

      <!-- Progress steps -->
      <div class="space-y-3 text-left">
        <div
          v-for="(s, i) in steps"
          :key="i"
          :class="[
            'flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-500',
            i < activeStep ? 'bg-emerald-50' : i === activeStep ? 'bg-white border border-emerald-200 shadow-sm' : 'opacity-40'
          ]"
        >
          <!-- Check / spinner / dot -->
          <div class="w-5 h-5 flex items-center justify-center shrink-0">
            <svg v-if="i < activeStep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-emerald-500">
              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
            </svg>
            <div v-else-if="i === activeStep && !failed && !insufficientContent" class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse" />
            <div v-else-if="insufficientContent && i === activeStep" class="w-3 h-3 rounded-full bg-amber-500" />
            <div v-else-if="failed && i === activeStep" class="w-3 h-3 rounded-full bg-red-500" />
            <div v-else class="w-2 h-2 rounded-full bg-gray-300" />
          </div>
          <span :class="['text-sm', i <= activeStep ? 'text-gray-700 font-medium' : 'text-gray-400']">
            {{ s.label }}
          </span>
        </div>
      </div>

      <!-- Insufficient content state -->
      <div v-if="insufficientContent" class="space-y-3">
        <p class="text-sm text-amber-700">
          The recording didn't contain enough clinical content to generate a visit summary.
          Please try recording a longer conversation (at least 1-2 minutes).
        </p>
        <button
          class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors"
          @click="router.push('/scribe')"
        >
          Record Again
        </button>
      </div>

      <!-- Error state -->
      <div v-else-if="failed" class="space-y-3">
        <p class="text-sm text-red-600">Something went wrong while processing your visit. Please try again.</p>
        <button
          class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors"
          @click="router.push('/scribe')"
        >
          Try Again
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
import LoadingParticles from '@/components/LoadingParticles.vue';

const router = useRouter();
const route = useRoute();
const api = useApi();

const steps = [
    { label: 'Transcribing audio...' },
    { label: 'Extracting clinical information...' },
    { label: 'Building your visit summary...' },
    { label: 'Cross-referencing with medical guidelines...' },
    { label: 'Checking medications...' },
    { label: 'Preparing your summary...' },
];

const activeStep = ref(0);
const failed = ref(false);
const insufficientContent = ref(false);
const elapsedSeconds = ref(0);
let pollInterval = null;
let timerInterval = null;

// Estimated processing time: ~1 sec processing per 5 sec of audio, minimum 30s
const audioDuration = parseInt(route.query.duration) || 0;
const estimatedTotalSeconds = computed(() => {
    if (!audioDuration) return 0;
    return Math.max(30, Math.round(audioDuration / 5));
});

const progressPercent = computed(() => {
    if (estimatedTotalSeconds.value <= 0) return 0;
    // Cap at 95% — last 5% fills when actually complete
    const raw = (elapsedSeconds.value / estimatedTotalSeconds.value) * 100;
    if (activeStep.value >= steps.length) return 100;
    return Math.min(95, raw);
});

const remainingText = computed(() => {
    if (activeStep.value >= steps.length) return '';
    const remaining = Math.max(0, estimatedTotalSeconds.value - elapsedSeconds.value);
    if (remaining <= 0) return 'finishing up...';
    if (remaining < 60) return `~${remaining}s remaining`;
    const m = Math.ceil(remaining / 60);
    return `~${m} min remaining`;
});

const elapsedFormatted = computed(() => {
    const m = Math.floor(elapsedSeconds.value / 60);
    const s = elapsedSeconds.value % 60;
    return m > 0 ? `${m}m ${s.toString().padStart(2, '0')}s` : `${s}s`;
});

const currentStep = computed(() => steps[activeStep.value] || steps[steps.length - 1]);

async function pollStatus() {
    const visitId = route.query.visitId;
    if (!visitId) {
        startSimulation();
        return;
    }

    pollInterval = setInterval(async () => {
        try {
            const { data } = await api.get(`/visits/${visitId}/transcript/status`, {
                skipErrorToast: true,
            });

            const status = data.data?.processing_status;
            const hasEntities = data.data?.has_entities;
            const hasSoapNote = data.data?.has_soap_note;

            if (status === 'completed') {
                clearInterval(pollInterval);
                clearInterval(timerInterval);
                activeStep.value = steps.length;
                setTimeout(() => {
                    router.push(`/visits/${visitId}`);
                }, 1000);
            } else if (status === 'failed') {
                clearInterval(pollInterval);
                clearInterval(timerInterval);
                failed.value = true;
            } else if (status === 'insufficient_content') {
                clearInterval(pollInterval);
                clearInterval(timerInterval);
                insufficientContent.value = true;
            } else if (hasSoapNote) {
                activeStep.value = 4;
            } else if (hasEntities) {
                activeStep.value = 2;
            } else if (status === 'transcribing') {
                activeStep.value = 0;
            } else if (status === 'processing') {
                activeStep.value = Math.max(activeStep.value, 1);
            }
        } catch {
            // Network error — keep polling silently
        }
    }, 3000);
}

function startSimulation() {
    let step = 0;
    const interval = setInterval(() => {
        if (step < steps.length - 1) {
            step++;
            activeStep.value = step;
        } else {
            clearInterval(interval);
            const visitId = route.query.visitId;
            if (visitId) {
                router.push(`/visits/${visitId}`);
            } else {
                router.push('/profile');
            }
        }
    }, 2000);
    pollInterval = interval;
}

onMounted(() => {
    timerInterval = setInterval(() => {
        elapsedSeconds.value++;
    }, 1000);
    pollStatus();
});

onUnmounted(() => {
    clearInterval(pollInterval);
    clearInterval(timerInterval);
});
</script>
