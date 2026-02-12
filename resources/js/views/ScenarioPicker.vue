<template>
  <div class="min-h-screen bg-gray-100 px-4 py-10 sm:px-6">
    <div class="max-w-6xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">PostVisit.ai — Select Demo Scenario</h1>
        <p class="text-sm text-gray-500 mt-2 max-w-2xl mx-auto">
          Select a patient to start a demo session. Each scenario loads a realistic visit transcript and clinical data relevant to the individual pathology.
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

      <template v-else>
        <!-- Featured Scenarios -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
          <button
            v-for="scenario in featuredScenarios"
            :key="scenario.key"
            :disabled="startingScenario !== null"
            class="text-left bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-200 disabled:opacity-60 disabled:cursor-wait group ring-2 ring-emerald-200 hover:ring-emerald-400"
            @click="selectScenario(scenario.key)"
          >
            <ScenarioCard :scenario="scenario" :index="scenarioIndex(scenario)" :starting="startingScenario === scenario.key" />
          </button>
        </div>

        <!-- Specialty Filter -->
        <div class="mt-8 flex flex-wrap items-center gap-2">
          <span class="text-xs font-medium text-gray-400 uppercase tracking-wider mr-1">Specialty:</span>
          <button
            v-for="spec in allSpecialties"
            :key="spec.name"
            :class="[
              'px-3 py-1 rounded-full text-xs font-medium transition-all duration-150',
              spec.available
                ? activeSpecialty === spec.name
                  ? 'bg-emerald-600 text-white shadow-sm'
                  : 'bg-white text-gray-600 hover:bg-emerald-50 hover:text-emerald-700 border border-gray-200'
                : 'bg-gray-100 text-gray-300 border border-gray-100 cursor-default'
            ]"
            @click="handleSpecialtyClick(spec)"
          >
            {{ spec.label }}
            <span v-if="spec.available && spec.count > 0" class="ml-1 text-[10px] opacity-60">{{ spec.count }}</span>
          </button>
        </div>

        <!-- More Scenarios -->
        <div v-if="otherScenarios.length > 0" class="mt-6">
          <button
            v-if="!showMore"
            class="w-full py-3 text-sm font-medium text-gray-500 hover:text-emerald-600 transition-colors flex items-center justify-center gap-2 bg-white rounded-lg border border-gray-200 hover:border-emerald-300"
            @click="showMore = true"
          >
            <span>Show {{ otherScenarios.length }} more scenarios</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <div v-else>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
              <button
                v-for="scenario in filteredOtherScenarios"
                :key="scenario.key"
                :disabled="startingScenario !== null"
                class="text-left bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-200 disabled:opacity-60 disabled:cursor-wait group"
                @click="selectScenario(scenario.key)"
              >
                <ScenarioCard :scenario="scenario" :index="scenarioIndex(scenario)" :starting="startingScenario === scenario.key" />
              </button>
            </div>

            <button
              class="mt-4 w-full py-2 text-xs text-gray-400 hover:text-gray-600 transition-colors"
              @click="showMore = false; activeSpecialty = null"
            >
              Show less
            </button>
          </div>
        </div>
      </template>

      <div v-if="startError" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 text-center max-w-lg mx-auto">
        {{ startError }}
      </div>

      <!-- Disclaimer -->
      <p class="text-center text-[11px] text-gray-400 mt-8 max-w-2xl mx-auto leading-relaxed">
        All patient photographs are AI-generated (Flux 2 Realism) and do not depict real individuals.
        Clinical scenarios span cardiology, endocrinology, gastroenterology, and pulmonology. All names, demographics, and medical data are entirely fictional.
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
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useApi } from '@/composables/useApi';
import { useRouter } from 'vue-router';
import ScenarioCard from '@/components/ScenarioCard.vue';

const auth = useAuthStore();
const router = useRouter();
const api = useApi();

const scenarios = ref([]);
const loadingScenarios = ref(true);
const fetchError = ref('');
const startingScenario = ref(null);
const startError = ref('');
const showMore = ref(false);
const activeSpecialty = ref(null);

const featuredScenarios = computed(() =>
  scenarios.value.filter(s => s.featured)
);

const otherScenarios = computed(() =>
  scenarios.value.filter(s => !s.featured)
);

const filteredOtherScenarios = computed(() => {
  if (!activeSpecialty.value) return otherScenarios.value;
  return otherScenarios.value.filter(s => s.specialty === activeSpecialty.value);
});

const KNOWN_SPECIALTIES = [
  { name: 'cardiology', label: 'Cardiology' },
  { name: 'endocrinology', label: 'Endocrinology' },
  { name: 'gastroenterology', label: 'Gastroenterology' },
  { name: 'pulmonology', label: 'Pulmonology' },
  { name: 'neurology', label: 'Neurology' },
  { name: 'orthopedics', label: 'Orthopedics' },
  { name: 'oncology', label: 'Oncology' },
  { name: 'rheumatology', label: 'Rheumatology' },
];

const allSpecialties = computed(() => {
  const counts = {};
  scenarios.value.forEach(s => {
    counts[s.specialty] = (counts[s.specialty] || 0) + 1;
  });
  return KNOWN_SPECIALTIES.map(spec => ({
    ...spec,
    available: !!counts[spec.name],
    count: counts[spec.name] || 0,
  }));
});

function scenarioIndex(scenario) {
  return scenarios.value.indexOf(scenario);
}

function handleSpecialtyClick(spec) {
  if (!spec.available) return;
  if (activeSpecialty.value === spec.name) {
    activeSpecialty.value = null;
  } else {
    activeSpecialty.value = spec.name;
  }
  if (!showMore.value) showMore.value = true;
}

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
