<template>
  <PatientLayout>
    <div class="space-y-6">
      <!-- Page header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Medical Library</h1>
        <p class="mt-1 text-sm text-gray-500">
          Evidence-based references and medical databases relevant to your care.
        </p>
      </div>

      <!-- OpenEvidence integration banner -->
      <div class="bg-gradient-to-r from-indigo-50 to-violet-50 rounded-2xl border border-indigo-100 p-4">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl bg-white border border-indigo-100 flex items-center justify-center shrink-0 shadow-sm">
            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
            </svg>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <p class="font-semibold text-indigo-900 text-sm">OpenEvidence Integration</p>
              <span class="text-[10px] font-medium bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Coming soon</span>
            </div>
            <p class="text-xs text-indigo-700/70 mt-0.5 leading-relaxed">
              AI-powered evidence-based answers cross-referenced with your visit context. Ask clinical questions and get responses grounded in peer-reviewed research.
            </p>
          </div>
        </div>
      </div>

      <!-- Tab navigation -->
      <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="activeTab === tab.id
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab: Relevant for You -->
      <div v-if="activeTab === 'relevant'" class="space-y-4">
        <div v-if="loadingRelevant" class="text-center py-12 text-gray-400">
          Loading your medical profile...
        </div>

        <template v-else>
          <!-- Your Conditions -->
          <div v-if="conditions.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 flex items-center justify-center rounded-lg bg-amber-50">
                  <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                  </svg>
                </span>
                <h2 class="font-semibold text-gray-800">Your Conditions</h2>
                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{{ conditions.length }}</span>
              </div>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="cond in conditions" :key="cond.id" class="p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-medium text-gray-900">{{ cond.code_display }}</p>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ cond.code }}</span>
                      <span v-if="cond.clinical_notes" class="text-xs text-gray-500">{{ cond.clinical_notes }}</span>
                    </div>
                  </div>
                  <button
                    class="shrink-0 text-xs text-emerald-600 hover:text-emerald-700 font-medium px-3 py-1.5 rounded-lg hover:bg-emerald-50 transition-colors"
                    @click="searchCondition(cond.code_display)"
                  >
                    Look up
                  </button>
                </div>
                <!-- Inline lookup result -->
                <div v-if="conditionLookups[cond.id]?.results?.length" class="mt-3 ml-4 pl-3 border-l-2 border-emerald-200 space-y-1">
                  <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Related conditions</p>
                  <div v-for="(match, i) in conditionLookups[cond.id].results.slice(0, 5)" :key="i" class="flex items-center gap-2 text-sm">
                    <span class="text-xs font-mono text-emerald-600">{{ match.code }}</span>
                    <span class="text-gray-700">{{ match.name }}</span>
                  </div>
                </div>
                <div v-if="conditionLookups[cond.id]?.loading" class="mt-2 text-xs text-gray-400">Searching...</div>
              </div>
            </div>
          </div>

          <!-- Your Medications -->
          <div v-if="medications.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 flex items-center justify-center rounded-lg bg-blue-50">
                  <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                  </svg>
                </span>
                <h2 class="font-semibold text-gray-800">Your Medications</h2>
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ medications.length }}</span>
              </div>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="rx in medications" :key="rx.id" class="p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-medium text-gray-900">{{ rx.medication?.display_name || rx.medication?.generic_name }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">
                      {{ parseFloat(rx.dose_quantity) }} {{ rx.dose_unit }} &middot; {{ rx.frequency_text || rx.frequency }}
                    </p>
                    <p v-if="rx.special_instructions" class="text-xs text-gray-400 mt-0.5">{{ rx.special_instructions }}</p>
                  </div>
                  <button
                    class="shrink-0 text-xs text-emerald-600 hover:text-emerald-700 font-medium px-3 py-1.5 rounded-lg hover:bg-emerald-50 transition-colors"
                    @click="lookupDrugLabel(rx)"
                  >
                    Drug info
                  </button>
                </div>
                <!-- Inline drug label -->
                <div v-if="drugLabels[rx.id]?.result" class="mt-3 ml-4 pl-3 border-l-2 border-blue-200 space-y-2">
                  <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">FDA Drug Label</p>
                  <div class="text-sm text-gray-700">
                    <p class="font-medium">{{ drugLabels[rx.id].result.title }}</p>
                    <p v-if="drugLabels[rx.id].result.author" class="text-xs text-gray-500 mt-0.5">{{ drugLabels[rx.id].result.author }}</p>
                  </div>
                </div>
                <div v-if="drugLabels[rx.id]?.loading" class="mt-2 text-xs text-gray-400">Looking up drug info...</div>
              </div>
            </div>
          </div>

          <!-- Medical References -->
          <div v-if="references.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 flex items-center justify-center rounded-lg bg-violet-50">
                  <svg class="w-3.5 h-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                  </svg>
                </span>
                <h2 class="font-semibold text-gray-800">Clinical References</h2>
                <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">{{ references.length }}</span>
              </div>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="ref in references" :key="ref.id" class="p-4">
                <div class="flex items-start gap-3">
                  <div class="min-w-0 flex-1">
                    <p class="font-medium text-gray-900 text-sm">{{ ref.title }}</p>
                    <p v-if="ref.authors" class="text-xs text-gray-500 mt-0.5">{{ ref.authors }}</p>
                    <div class="flex items-center gap-2 mt-1.5">
                      <span v-if="ref.journal" class="text-xs text-gray-500 italic">{{ ref.journal }}</span>
                      <span v-if="ref.year" class="text-xs text-gray-400">({{ ref.year }})</span>
                      <span v-if="ref.verified" class="inline-flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 010-5.304 3 3 0 00-3.75-3.751 3 3 0 00-5.305 0 3 3 0 00-3.751 3.75 3 3 0 000 5.305 3 3 0 003.75 3.751 3 3 0 005.305 0 3 3 0 003.751-3.75zm-2.546-4.46a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                        PubMed Verified
                      </span>
                      <span v-if="ref.category" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ ref.category }}</span>
                    </div>
                  </div>
                  <a
                    v-if="ref.url || ref.doi"
                    :href="ref.url || `https://doi.org/${ref.doi}`"
                    target="_blank"
                    rel="noopener"
                    class="shrink-0 text-xs text-blue-600 hover:text-blue-700 font-medium px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors"
                  >
                    View source
                  </a>
                </div>
                <p v-if="ref.summary" class="text-sm text-gray-600 mt-2 leading-relaxed">{{ ref.summary }}</p>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="!conditions.length && !medications.length && !references.length" class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
            <p class="text-gray-500">No personalized references available yet. Complete a visit to see relevant medical information here.</p>
          </div>
        </template>
      </div>

      <!-- Tab: Ask AI (EBM) — OpenEvidence mockup -->
      <div v-if="activeTab === 'ask'" class="space-y-4">
        <!-- OpenEvidence powered search -->
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
          <form class="space-y-3" @submit.prevent="askOpenEvidence">
            <div class="relative">
              <svg class="absolute left-3 top-3 w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
              </svg>
              <input
                v-model="oeQuery"
                type="text"
                placeholder="Ask a clinical question..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-shadow"
              />
            </div>
            <div class="flex items-center justify-between">
              <p class="text-[11px] text-gray-400">Answers cross-referenced with your visit context and EBM sources</p>
              <button
                type="submit"
                :disabled="oeLoading || !oeQuery?.trim()"
                class="px-5 py-2 bg-indigo-600 text-white text-sm rounded-xl font-medium hover:bg-indigo-700 transition-colors disabled:opacity-40 shrink-0"
              >
                {{ oeLoading ? 'Searching...' : 'Ask' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Suggested questions from visit context -->
        <div v-if="!oeResult && !oeLoading" class="space-y-3">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide px-1">Suggested from your visit</p>
          <button
            v-for="(sq, i) in suggestedQuestions"
            :key="i"
            class="w-full text-left bg-white rounded-xl border border-gray-200 p-3.5 hover:border-indigo-200 hover:bg-indigo-50/30 transition-colors group"
            @click="oeQuery = sq.question; askOpenEvidence()"
          >
            <p class="text-sm font-medium text-gray-800 group-hover:text-indigo-900">{{ sq.question }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ sq.context }}</p>
          </button>
        </div>

        <!-- Loading state -->
        <div v-if="oeLoading" class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-3">
          <div class="w-8 h-8 border-2 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto" />
          <p class="text-sm text-gray-500">Searching evidence-based sources...</p>
          <p class="text-xs text-gray-400">Cross-referencing PubMed, Cochrane, and clinical guidelines</p>
        </div>

        <!-- Mock result -->
        <div v-if="oeResult" class="space-y-4">
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50/50 to-transparent">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
                <p class="text-sm font-semibold text-indigo-900">Evidence-Based Answer</p>
              </div>
            </div>
            <div class="p-4 space-y-3">
              <p class="text-sm text-gray-800 leading-relaxed">{{ oeResult.answer }}</p>

              <div v-if="oeResult.confidence" class="flex items-center gap-2">
                <span class="text-xs font-medium text-gray-500">Evidence strength:</span>
                <div class="flex gap-0.5">
                  <div v-for="n in 5" :key="n" class="w-4 h-1.5 rounded-full" :class="n <= oeResult.confidence ? 'bg-indigo-500' : 'bg-gray-200'" />
                </div>
                <span class="text-xs text-gray-400">{{ oeResult.evidenceLevel }}</span>
              </div>
            </div>
          </div>

          <!-- Sources -->
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="p-3 border-b border-gray-100">
              <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Sources ({{ oeResult.sources.length }})</p>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="(src, i) in oeResult.sources" :key="i" class="p-3 flex items-start gap-2">
                <span class="shrink-0 w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold mt-0.5">{{ i + 1 }}</span>
                <div class="min-w-0">
                  <p class="text-xs font-medium text-gray-800">{{ src.title }}</p>
                  <p class="text-[11px] text-gray-500 mt-0.5">{{ src.journal }} ({{ src.year }})</p>
                  <span v-if="src.type" class="inline-block mt-1 text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full">{{ src.type }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Disclaimer -->
          <p class="text-[11px] text-gray-400 text-center px-4">
            Powered by OpenEvidence. Answers are generated from peer-reviewed literature and clinical guidelines. Always verify with your healthcare provider.
          </p>
        </div>
      </div>

      <!-- Tab: Search -->
      <div v-if="activeTab === 'search'" class="space-y-4">
        <!-- Search input -->
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
          <form class="flex gap-2" @submit.prevent="runSearch">
            <div class="flex-1 relative">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search conditions, drugs, procedures..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow"
                @keyup.enter="runSearch"
              />
            </div>
            <select
              v-model="searchType"
              class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
              <option value="conditions">Conditions</option>
              <option value="drugs">Drugs</option>
              <option value="procedures">Procedures</option>
              <option value="drug-label">Drug Label</option>
            </select>
            <button
              type="submit"
              :disabled="searchLoading || !searchQuery || searchQuery.trim().length < 2"
              class="px-5 py-2.5 bg-emerald-600 text-white text-sm rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-40 shrink-0"
            >
              {{ searchLoading ? 'Searching...' : 'Search' }}
            </button>
          </form>
        </div>

        <!-- Search results -->
        <div v-if="searchLoading" class="text-center py-8 text-gray-400">Searching medical databases...</div>

        <div v-else-if="searchResults.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <div class="p-4 border-b border-gray-100">
            <p class="text-sm text-gray-500">{{ searchResults.length }} result{{ searchResults.length !== 1 ? 's' : '' }} from {{ searchSourceLabel }}</p>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="(item, i) in searchResults" :key="i" class="p-4 flex items-start gap-3">
              <span v-if="item.code" class="shrink-0 mt-0.5 text-xs font-mono bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">{{ item.code }}</span>
              <div>
                <p class="text-sm font-medium text-gray-800">{{ item.name || item.title }}</p>
                <p v-if="item.extra" class="text-xs text-gray-500 mt-0.5">{{ item.extra }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="searchDrugLabelResult" class="bg-white rounded-2xl border border-gray-200 p-6 space-y-3">
          <h3 class="font-semibold text-gray-900">{{ searchDrugLabelResult.title }}</h3>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-xs font-medium text-gray-400 uppercase">Author</p>
              <p class="text-gray-800 mt-0.5">{{ searchDrugLabelResult.author || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-400 uppercase">Version</p>
              <p class="text-gray-800 mt-0.5">{{ searchDrugLabelResult.version_number || 'N/A' }}</p>
            </div>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase">SPL Set ID</p>
            <p class="text-xs font-mono text-gray-500 mt-0.5">{{ searchDrugLabelResult.setid || 'N/A' }}</p>
          </div>
        </div>

        <div v-else-if="searchSearched && !searchLoading" class="text-center py-8 text-gray-400 text-sm">
          No results found. Try a different search term.
        </div>

        <!-- Data source attribution -->
        <div class="text-xs text-gray-400 text-center space-y-0.5">
          <p>Powered by NIH Clinical Tables, NLM RxTerms, DailyMed, and PubMed</p>
          <p>Medical references are for informational purposes only. Always consult your healthcare provider.</p>
        </div>
      </div>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';
import { useAuthStore } from '@/stores/auth';
import PatientLayout from '@/layouts/PatientLayout.vue';

const api = useApi();
const auth = useAuthStore();

const tabs = [
    { id: 'relevant', label: 'Relevant for You' },
    { id: 'ask', label: 'Ask AI (EBM)' },
    { id: 'search', label: 'Search Databases' },
];
const activeTab = ref('relevant');

// --- Relevant for You ---
const loadingRelevant = ref(true);
const conditions = ref([]);
const medications = ref([]);
const references = ref([]);
const conditionLookups = reactive({});
const drugLabels = reactive({});

async function loadRelevantData() {
    loadingRelevant.value = true;
    const patientId = auth.user?.patient_id || auth.user?.patient?.id;
    if (!patientId) {
        loadingRelevant.value = false;
        return;
    }

    try {
        const [condRes, rxRes, refRes] = await Promise.all([
            api.get(`/patients/${patientId}/conditions`).catch(() => ({ data: { data: [] } })),
            api.get(`/patients/${patientId}/prescriptions`).catch(() => ({ data: { data: [] } })),
            api.get('/references').catch(() => ({ data: { data: [] } })),
        ]);
        conditions.value = condRes.data.data || [];
        medications.value = rxRes.data.data || [];
        references.value = refRes.data.data || [];
    } catch {
        // Non-blocking
    } finally {
        loadingRelevant.value = false;
    }
}

async function searchCondition(name) {
    const cond = conditions.value.find(c => c.code_display === name);
    if (!cond) return;
    const id = cond.id;

    conditionLookups[id] = { loading: true, results: [] };
    try {
        const { data } = await api.get('/lookup/conditions', { params: { q: name } });
        conditionLookups[id] = { loading: false, results: data.data?.matches || [] };
    } catch {
        conditionLookups[id] = { loading: false, results: [] };
    }
}

async function lookupDrugLabel(rx) {
    const name = rx.medication?.generic_name || rx.medication?.display_name;
    if (!name) return;

    drugLabels[rx.id] = { loading: true, result: null };
    try {
        const { data } = await api.get('/lookup/drug-label', { params: { drug_name: name } });
        const label = data.data;
        drugLabels[rx.id] = { loading: false, result: label && Object.keys(label).length > 0 ? label : null };
    } catch {
        drugLabels[rx.id] = { loading: false, result: null };
    }
}

// --- Ask AI (OpenEvidence mockup) ---
const oeQuery = ref('');
const oeLoading = ref(false);
const oeResult = ref(null);

const suggestedQuestions = computed(() => {
    const questions = [];
    if (conditions.value.length) {
        const cond = conditions.value[0];
        questions.push({
            question: `What are the latest guidelines for managing ${cond.code_display}?`,
            context: `Based on your diagnosis`,
        });
    }
    if (medications.value.length) {
        const rx = medications.value[0];
        const name = rx.medication?.display_name || rx.medication?.generic_name;
        questions.push({
            question: `What are the common side effects of ${name} I should watch for?`,
            context: `Based on your current medication`,
        });
        if (conditions.value.length) {
            questions.push({
                question: `Is ${name} first-line therapy for ${conditions.value[0].code_display}?`,
                context: `Cross-referencing your medication and diagnosis`,
            });
        }
    }
    if (!questions.length) {
        questions.push(
            { question: 'What lifestyle changes help manage premature ventricular contractions?', context: 'Common cardiology question' },
            { question: 'When should I seek emergency care for heart palpitations?', context: 'Safety information' },
            { question: 'How does propranolol work for PVCs?', context: 'Medication mechanism' },
        );
    }
    return questions;
});

const mockAnswers = {
    default: {
        answer: 'Based on current clinical evidence, beta-blockers such as propranolol are considered first-line therapy for symptomatic premature ventricular contractions (PVCs). The ACC/AHA 2024 guidelines recommend a starting dose of 10-40mg two to three times daily, titrated to symptom control. Lifestyle modifications including caffeine reduction, stress management, and adequate sleep are also strongly recommended as adjunctive measures. If PVC burden exceeds 15-20% on Holter monitoring, catheter ablation should be discussed.',
        confidence: 4,
        evidenceLevel: 'Level A — Strong',
        sources: [
            { title: 'ACC/AHA Guideline for Management of Ventricular Arrhythmias', journal: 'Circulation', year: '2024', type: 'Clinical Guideline' },
            { title: 'Beta-blocker therapy for premature ventricular complexes: systematic review', journal: 'Heart Rhythm', year: '2023', type: 'Systematic Review' },
            { title: 'PVC burden and cardiomyopathy risk: a prospective cohort study', journal: 'JACC', year: '2023', type: 'Cohort Study' },
            { title: 'Lifestyle interventions for arrhythmia management', journal: 'European Heart Journal', year: '2024', type: 'Review Article' },
        ],
    },
};

async function askOpenEvidence() {
    if (!oeQuery.value?.trim()) return;
    oeLoading.value = true;
    oeResult.value = null;

    // Simulate API call delay
    await new Promise(resolve => setTimeout(resolve, 2500));

    oeResult.value = {
        answer: mockAnswers.default.answer,
        confidence: mockAnswers.default.confidence,
        evidenceLevel: mockAnswers.default.evidenceLevel,
        sources: mockAnswers.default.sources,
    };
    oeLoading.value = false;
}

// --- Search tab ---
const searchQuery = ref('');
const searchType = ref('conditions');
const searchLoading = ref(false);
const searchResults = ref([]);
const searchDrugLabelResult = ref(null);
const searchSearched = ref(false);

const searchSourceLabel = computed(() => ({
    conditions: 'NIH Clinical Tables (ICD-10)',
    drugs: 'NLM RxTerms',
    procedures: 'HCPCS',
    'drug-label': 'DailyMed',
}[searchType.value]));

async function runSearch() {
    const q = searchQuery.value?.trim();
    if (!q || q.length < 2) return;

    searchLoading.value = true;
    searchResults.value = [];
    searchDrugLabelResult.value = null;

    try {
        if (searchType.value === 'drug-label') {
            const { data } = await api.get('/lookup/drug-label', { params: { drug_name: q } });
            const label = data.data;
            searchDrugLabelResult.value = label && Object.keys(label).length > 0 ? label : null;
        } else {
            const endpoint = `/lookup/${searchType.value}`;
            const { data } = await api.get(endpoint, { params: { q } });
            searchResults.value = data.data?.matches || [];
        }
    } catch {
        // Handled by empty state
    } finally {
        searchLoading.value = false;
        searchSearched.value = true;
    }
}

onMounted(() => {
    loadRelevantData();
});
</script>
