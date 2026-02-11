<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-50 to-white px-4 py-12">
    <div class="max-w-3xl mx-auto">
      <div class="text-center mb-10">
        <router-link to="/" class="text-2xl font-bold text-emerald-700">PostVisit.ai</router-link>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Choose a Demo Scenario</h1>
        <p class="text-gray-500 mt-2">
          Select a patient profile to explore. Each scenario creates a fresh session with realistic clinical data.
        </p>
      </div>

      <div v-if="loadingScenarios" class="text-center py-16">
        <div class="inline-block w-8 h-8 border-4 border-emerald-200 border-t-emerald-600 rounded-full animate-spin"></div>
        <p class="text-gray-500 mt-3">Loading scenarios...</p>
      </div>

      <div v-else-if="fetchError" class="text-center py-16">
        <p class="text-red-600 mb-4">{{ fetchError }}</p>
        <button
          class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
          @click="loadScenarios"
        >
          Retry
        </button>
      </div>

      <div v-else class="grid gap-6 sm:grid-cols-2">
        <button
          v-for="scenario in scenarios"
          :key="scenario.key"
          :disabled="startingScenario !== null"
          class="text-left bg-white rounded-2xl border-2 p-6 transition-all hover:shadow-lg disabled:opacity-60 disabled:cursor-wait"
          :class="scenarioBorderClass(scenario)"
          @click="selectScenario(scenario.key)"
        >
          <div class="flex items-start gap-4">
            <div
              class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
              :class="scenarioIconBgClass(scenario)"
            >
              <svg v-if="scenario.icon === 'heart-pulse'" class="w-6 h-6" :class="scenarioIconClass(scenario)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.343 7.778a4.5 4.5 0 0 1 7.339-1.46L12 7.636l1.318-1.318a4.5 4.5 0 0 1 7.339 1.46c.974 2.14.19 4.652-1.414 6.256L12 21.364l-7.243-7.23C3.153 12.43 2.37 9.918 3.343 7.778Z" />
              </svg>
              <svg v-else class="w-6 h-6" :class="scenarioIconClass(scenario)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-9 4 18 3-9h4" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-semibold text-gray-900">{{ scenario.name }}</h3>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-sm font-medium text-gray-700">{{ scenario.patient_name }}</span>
                <span class="text-xs text-gray-400">{{ scenario.patient_age }}{{ scenario.patient_gender === 'male' ? 'M' : 'F' }}</span>
              </div>
              <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ scenario.description }}</p>
              <div v-if="scenario.condition" class="mt-3">
                <span
                  class="inline-block text-xs font-medium px-2.5 py-1 rounded-full"
                  :class="scenarioBadgeClass(scenario)"
                >
                  {{ scenario.condition }}
                </span>
              </div>
            </div>
          </div>

          <div v-if="startingScenario === scenario.key" class="mt-4 flex items-center gap-2 text-sm text-gray-500">
            <div class="w-4 h-4 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin"></div>
            Creating your session...
          </div>
        </button>
      </div>

      <div v-if="startError" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 text-center">
        {{ startError }}
      </div>

      <div class="text-center mt-8">
        <router-link to="/login" class="text-sm text-gray-500 hover:text-emerald-600 transition-colors">
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

function scenarioBorderClass(scenario) {
  return scenario.color === 'rose'
    ? 'border-rose-200 hover:border-rose-400'
    : 'border-emerald-200 hover:border-emerald-400';
}

function scenarioIconBgClass(scenario) {
  return scenario.color === 'rose' ? 'bg-rose-100' : 'bg-emerald-100';
}

function scenarioIconClass(scenario) {
  return scenario.color === 'rose' ? 'text-rose-600' : 'text-emerald-600';
}

function scenarioBadgeClass(scenario) {
  return scenario.color === 'rose'
    ? 'bg-rose-100 text-rose-700'
    : 'bg-emerald-100 text-emerald-700';
}

onMounted(loadScenarios);
</script>
