<template>
  <PatientLayout>
    <div class="max-w-2xl mx-auto space-y-8">
      <!-- Page header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Medical Lookup</h1>
        <p class="mt-1 text-sm text-gray-500">
          Search medical databases powered by NIH Clinical Tables and DailyMed.
        </p>
      </div>

      <!-- Conditions Search -->
      <LookupCard
        title="Conditions (ICD-10)"
        description="Search diagnoses and conditions by name or ICD-10 code"
        placeholder="e.g. hypertension, diabetes, I49.3..."
        :loading="conditions.loading"
        :error="conditions.error"
        v-model="conditions.query"
        @search="searchConditions"
      >
        <ul v-if="conditions.results.length" class="divide-y divide-gray-100">
          <li
            v-for="(item, i) in conditions.results"
            :key="i"
            class="flex items-start gap-3 py-3 first:pt-0 last:pb-0"
          >
            <span class="shrink-0 mt-0.5 text-xs font-mono bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">
              {{ item.code }}
            </span>
            <span class="text-sm text-gray-700">{{ item.name }}</span>
          </li>
        </ul>
        <EmptyState v-else-if="conditions.searched && !conditions.loading" />
      </LookupCard>

      <!-- Drugs Search -->
      <LookupCard
        title="Drugs (RxTerms)"
        description="Search drug names with autocompletion from NLM RxTerms"
        placeholder="e.g. propranolol, metformin, atorvastatin..."
        :loading="drugs.loading"
        :error="drugs.error"
        v-model="drugs.query"
        @search="searchDrugs"
      >
        <ul v-if="drugs.results.length" class="divide-y divide-gray-100">
          <li
            v-for="(item, i) in drugs.results"
            :key="i"
            class="py-3 first:pt-0 last:pb-0"
          >
            <p class="text-sm font-medium text-gray-800">{{ item.name }}</p>
            <p v-if="item.extra" class="text-xs text-gray-400 mt-0.5">{{ item.extra }}</p>
          </li>
        </ul>
        <EmptyState v-else-if="drugs.searched && !drugs.loading" />
      </LookupCard>

      <!-- Procedures Search -->
      <LookupCard
        title="Procedures (HCPCS)"
        description="Search medical procedures and services by name or code"
        placeholder="e.g. echocardiogram, MRI, blood panel..."
        :loading="procedures.loading"
        :error="procedures.error"
        v-model="procedures.query"
        @search="searchProcedures"
      >
        <ul v-if="procedures.results.length" class="divide-y divide-gray-100">
          <li
            v-for="(item, i) in procedures.results"
            :key="i"
            class="py-3 first:pt-0 last:pb-0"
          >
            <p class="text-sm text-gray-700">{{ item.name }}</p>
          </li>
        </ul>
        <EmptyState v-else-if="procedures.searched && !procedures.loading" />
      </LookupCard>

      <!-- Drug Label Search -->
      <LookupCard
        title="Drug Label (DailyMed)"
        description="Get official FDA drug label information from DailyMed"
        placeholder="e.g. propranolol, ibuprofen, lisinopril..."
        param-name="drug_name"
        :loading="drugLabel.loading"
        :error="drugLabel.error"
        v-model="drugLabel.query"
        @search="searchDrugLabel"
      >
        <div v-if="drugLabel.result" class="space-y-3">
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Title</p>
            <p class="text-sm text-gray-800 mt-0.5">{{ drugLabel.result.title || 'N/A' }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Author</p>
              <p class="text-sm text-gray-800 mt-0.5">{{ drugLabel.result.author || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Version</p>
              <p class="text-sm text-gray-800 mt-0.5">{{ drugLabel.result.version_number || 'N/A' }}</p>
            </div>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Effective Date</p>
            <p class="text-sm text-gray-800 mt-0.5">{{ formatDate(drugLabel.result.effective_time) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">SPL Set ID</p>
            <p class="text-xs font-mono text-gray-500 mt-0.5">{{ drugLabel.result.setid || 'N/A' }}</p>
          </div>
        </div>
        <EmptyState v-else-if="drugLabel.searched && !drugLabel.loading" message="No label found for this drug." />
      </LookupCard>
    </div>
  </PatientLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { useApi } from '@/composables/useApi';
import PatientLayout from '@/layouts/PatientLayout.vue';

const api = useApi();

// --- Reactive state for each search section ---

function createSearchState() {
  return reactive({
    query: '',
    results: [],
    result: null,
    loading: false,
    error: null,
    searched: false,
  });
}

const conditions = createSearchState();
const drugs = createSearchState();
const procedures = createSearchState();
const drugLabel = createSearchState();

// --- Search functions ---

async function searchConditions() {
  await performSearch(conditions, '/lookup/conditions', { q: conditions.query }, 'matches');
}

async function searchDrugs() {
  await performSearch(drugs, '/lookup/drugs', { q: drugs.query }, 'matches');
}

async function searchProcedures() {
  await performSearch(procedures, '/lookup/procedures', { q: procedures.query }, 'matches');
}

async function searchDrugLabel() {
  await performSearch(drugLabel, '/lookup/drug-label', { drug_name: drugLabel.query }, 'single');
}

/**
 * Generic search handler.
 * mode='matches' extracts data.matches array, mode='single' uses data directly.
 */
async function performSearch(state, endpoint, params, mode) {
  if (!Object.values(params).every(v => v && v.trim().length >= 2)) return;

  state.loading = true;
  state.error = null;
  state.results = [];
  state.result = null;

  try {
    const { data } = await api.get(endpoint, { params });

    if (mode === 'matches') {
      state.results = data.data?.matches ?? [];
    } else {
      // Single result (drug label) -- check if we got meaningful data
      const label = data.data;
      state.result = label && Object.keys(label).length > 0 ? label : null;
    }
  } catch (err) {
    state.error = err.response?.data?.message || 'Search failed. Please try again.';
  } finally {
    state.loading = false;
    state.searched = true;
  }
}

// --- Helpers ---

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  // DailyMed dates come as "YYYYMMDD"
  if (/^\d{8}$/.test(dateStr)) {
    return `${dateStr.slice(0, 4)}-${dateStr.slice(4, 6)}-${dateStr.slice(6, 8)}`;
  }
  return dateStr;
}

// --- Inline sub-components ---

const LookupCard = {
  props: {
    title: String,
    description: String,
    placeholder: { type: String, default: 'Search...' },
    loading: Boolean,
    error: String,
    modelValue: String,
  },
  emits: ['update:modelValue', 'search'],
  template: `
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
      <div class="p-6 space-y-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">{{ title }}</h2>
          <p class="text-xs text-gray-400 mt-0.5">{{ description }}</p>
        </div>

        <form class="flex gap-2" @submit.prevent="$emit('search')">
          <input
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            @keyup.enter="$emit('search')"
            type="text"
            :placeholder="placeholder"
            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow"
          />
          <button
            type="submit"
            :disabled="loading || !modelValue || modelValue.trim().length < 2"
            class="px-5 py-2.5 bg-emerald-600 text-white text-sm rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
          >
            <span v-if="loading" class="flex items-center gap-1.5">
              <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Searching
            </span>
            <span v-else>Search</span>
          </button>
        </form>

        <div v-if="error" class="text-sm text-red-600 bg-red-50 rounded-xl px-4 py-2.5">
          {{ error }}
        </div>

        <div v-if="!error">
          <slot />
        </div>
      </div>
    </div>
  `,
};

const EmptyState = {
  props: {
    message: { type: String, default: 'No results found. Try a different search term.' },
  },
  template: `
    <p class="text-sm text-gray-400 text-center py-4">{{ message }}</p>
  `,
};
</script>
