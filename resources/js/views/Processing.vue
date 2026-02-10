<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-emerald-50 to-white px-4">
    <div class="text-center space-y-8 max-w-md">
      <LoadingParticles />

      <div class="space-y-2">
        <h1 class="text-2xl font-semibold text-gray-800">Analyzing your visit...</h1>
        <p class="text-gray-500 text-sm">{{ currentStep.label }}</p>
      </div>

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
            <div v-else-if="i === activeStep" class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse" />
            <div v-else class="w-2 h-2 rounded-full bg-gray-300" />
          </div>
          <span :class="['text-sm', i <= activeStep ? 'text-gray-700 font-medium' : 'text-gray-400']">
            {{ s.label }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useVisitStore } from '@/stores/visit';
import LoadingParticles from '@/components/LoadingParticles.vue';

const router = useRouter();
const visitStore = useVisitStore();

const steps = [
    { label: 'Transcribing audio...' },
    { label: 'Extracting clinical information...' },
    { label: 'Building SOAP note...' },
    { label: 'Checking medications...' },
    { label: 'Preparing your summary...' },
];

const activeStep = ref(0);
let interval = null;

const currentStep = computed(() => steps[activeStep.value] || steps[steps.length - 1]);

onMounted(() => {
    // Simulate processing steps (in production, poll /api/v1/transcripts/{id}/status)
    interval = setInterval(() => {
        if (activeStep.value < steps.length - 1) {
            activeStep.value++;
        } else {
            clearInterval(interval);
            // Auto-redirect to the visit view
            const visitId = visitStore.currentVisit?.id;
            if (visitId) {
                router.push(`/visit/${visitId}`);
            } else {
                // Fallback: redirect to patient profile
                router.push('/profile');
            }
        }
    }, 2000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>
