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
        <div class="w-32 h-32 mx-auto rounded-full bg-red-100 flex items-center justify-center animate-pulse">
          <div class="w-16 h-16 rounded-full bg-red-500" />
        </div>
        <p class="text-sm text-gray-500">{{ formattedTime }}</p>
        <button
          class="w-full py-3 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors"
          @click="stopRecording"
        >
          Stop Recording
        </button>
      </div>

      <!-- Post-recording step -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">Recording Complete</h2>
        <p class="text-gray-500">{{ formattedTime }} recorded</p>
        <p class="text-sm text-gray-500">You can attach additional files to this visit.</p>
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

const step = ref('consent');
const seconds = ref(0);
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
