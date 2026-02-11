<template>
  <div class="min-h-screen bg-gray-100 px-4 py-10 sm:px-6">
    <div class="max-w-6xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">PostVisit.ai — Patient Portraits</h1>
        <p class="text-sm text-gray-500 mt-2 max-w-2xl mx-auto">
          Select a patient to start a demo session. Each scenario creates a fresh session with realistic clinical data, visit transcript, and AI-generated SOAP notes.
        </p>
      </div>

      <div v-if="loadingScenarios" class="text-center py-20">
        <div class="inline-block w-8 h-8 border-4 border-emerald-200 border-t-emerald-600 rounded-full animate-spin"></div>
        <p class="text-gray-500 mt-3">Loading scenarios...</p>
      </div>

      <div v-else-if="fetchError" class="text-center py-20">
        <p class="text-red-600 mb-4">{{ fetchError }}</p>
        <button
          class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
          @click="loadScenarios"
        >
          Retry
        </button>
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        <button
          v-for="(scenario, idx) in scenarios"
          :key="scenario.key"
          :disabled="startingScenario !== null"
          class="text-left bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-200 disabled:opacity-60 disabled:cursor-wait group"
          @click="selectScenario(scenario.key)"
        >
          <!-- Photo -->
          <div class="aspect-square overflow-hidden bg-gray-200 relative">
            <img
              v-if="scenario.photo_url"
              :src="scenario.photo_url"
              :alt="scenario.patient_name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
              <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
              </svg>
            </div>
            <!-- Loading overlay -->
            <div
              v-if="startingScenario === scenario.key"
              class="absolute inset-0 bg-black/40 flex items-center justify-center"
            >
              <div class="w-8 h-8 border-3 border-white/30 border-t-white rounded-full animate-spin"></div>
            </div>
            <!-- Language badge -->
            <span
              v-if="scenario.language"
              class="absolute top-2 right-2 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-black/50 text-white"
            >
              {{ scenario.language }}
            </span>
          </div>

          <!-- Info -->
          <div class="px-3 py-2.5 sm:px-4 sm:py-3">
            <div class="font-bold text-gray-900 text-sm sm:text-base leading-tight">
              {{ String(idx + 1).padStart(2, '0') }} — {{ scenario.patient_name }}
            </div>
            <div class="text-xs sm:text-sm text-gray-500 mt-0.5">
              {{ scenario.patient_age }}{{ scenario.patient_gender === 'male' ? 'M' : 'F' }}
              <template v-if="scenario.bmi"> · BMI {{ scenario.bmi }}</template>
            </div>
            <div v-if="scenario.condition" class="text-xs sm:text-[13px] text-emerald-600 italic mt-1 line-clamp-2 leading-snug">
              {{ scenario.description }}
            </div>
          </div>
        </button>
      </div>

      <div v-if="startError" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 text-center max-w-lg mx-auto">
        {{ startError }}
      </div>

      <!-- Disclaimer -->
      <p class="text-center text-[11px] text-gray-400 mt-8 max-w-2xl mx-auto leading-relaxed">
        All patient photographs are AI-generated (Flux 2 Realism) and do not depict real individuals.
        Clinical scenarios are based on representative cardiology cases. All names, demographics, and medical data are entirely fictional.
      </p>

      <div class="text-center mt-4">
        <router-link to="/login" class="text-sm text-gray-400 hover:text-emerald-600 transition-colors">
          Back to Sign In
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useApi } from '@/composables/useApi';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const api = useApi();

const scenarios = ref([]);
const loadingScenarios = ref(true);
const fetchError = ref('');
const startingScenario = ref(null);
const startError = ref('');

async function loadScenarios() {
  loadingScenarios.value = true;
  fetchError.value = '';
  try {
    const { data } = await api.get('/demo/scenarios', { skipErrorToast: true });
    scenarios.value = data.data;
  } catch (err) {
    fetchError.value = 'Could not load demo scenarios. Please try again.';
  } finally {
    loadingScenarios.value = false;
  }
}

async function selectScenario(key) {
  startingScenario.value = key;
  startError.value = '';
  try {
    const { data } = await api.post('/demo/start-scenario', { scenario: key });
    auth.user = data.data.user;
    auth.token = data.data.token;
    router.push('/profile');
  } catch (err) {
    startError.value = err.response?.data?.error?.message || 'Failed to start scenario. Please try again.';
    startingScenario.value = null;
  }
}

onMounted(loadScenarios);
</script>
