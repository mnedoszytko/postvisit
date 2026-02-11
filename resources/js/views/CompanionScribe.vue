<template>
  <PatientLayout>
    <div class="max-w-lg mx-auto space-y-6">
      <!-- Consent step -->
      <div v-if="step === 'consent'" class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">Companion Scribe</h1>
        <p class="text-gray-600">
          Record your doctor's visit to get a complete, understandable summary afterwards.
        </p>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
          Both the provider and the patient consent to this recording.
        </div>
        <button
          class="w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
          @click="step = 'recording'"
        >
          I Consent — Start Recording
        </button>
      </div>

      <!-- Recording step -->
      <div v-else-if="step === 'recording'" class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-6">
        <h2 class="text-lg font-semibold text-gray-800">Recording in progress...</h2>

        <ThreeVisualizer />

        <p class="text-2xl font-mono text-gray-700 tracking-widest">{{ formattedTime }}</p>
        <p class="text-xs text-gray-400">Your conversation is being captured securely</p>

        <button
          class="w-full py-3 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors"
          @click="stopRecording"
        >
          Stop Recording
        </button>
      </div>

      <!-- Post-recording step -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-4">
        <div class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-emerald-600">
            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
          </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-800">Recording Complete</h2>
        <p class="text-gray-500">{{ formattedTime }} recorded</p>
        <router-link
          to="/processing"
          class="block w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
        >
          Process Visit
        </router-link>
      </div>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import PatientLayout from '@/layouts/PatientLayout.vue';
import RecordingAnimation from '@/components/RecordingAnimation.vue';
import ThreeVisualizer from '@/components/ThreeVisualizer.vue';

const step = ref('consent');
const seconds = ref(0);
const animationVariant = ref('ripples');
let timer = null;

const formattedTime = computed(() => {
    const m = Math.floor(seconds.value / 60).toString().padStart(2, '0');
    const s = (seconds.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

function stopRecording() {
    clearInterval(timer);
    step.value = 'done';
}

watch(step, (val) => {
    if (val === 'recording') {
        seconds.value = 0;
        timer = setInterval(() => seconds.value++, 1000);
    }
});

onUnmounted(() => {
    clearInterval(timer);
});
</script>
