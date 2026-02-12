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
                    <p class="font-medium">{{ drugLabels[rx.id]?.result?.title }}</p>
                    <p v-if="drugLabels[rx.id]?.result?.author" class="text-xs text-gray-500 mt-0.5">{{ drugLabels[rx.id]?.result?.author }}</p>
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

      <!-- Tab: Ask (EBM) — OpenEvidence mockup -->
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

      <!-- Tab: My Library -->
      <div v-if="activeTab === 'my-library'" class="space-y-4">
        <!-- Upload Zone -->
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <div class="p-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <span class="w-6 h-6 flex items-center justify-center rounded-lg bg-indigo-50">
                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                </svg>
              </span>
              <h2 class="font-semibold text-gray-800">Add to Your Library</h2>
            </div>
          </div>
          <div class="p-4 space-y-3">
            <!-- Drag and drop zone -->
            <div
              class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
              :class="dragOver ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/50'"
              @dragover.prevent="dragOver = true"
              @dragleave.prevent="dragOver = false"
              @drop.prevent="handleDrop"
              @click="fileInput?.click()"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".pdf"
                class="hidden"
                @change="uploadPdf"
              />
              <div v-if="uploadingFile" class="space-y-2">
                <div class="w-8 h-8 border-2 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto" />
                <p class="text-sm text-indigo-600 font-medium">Uploading...</p>
              </div>
              <div v-else class="space-y-1.5">
                <svg class="w-8 h-8 text-gray-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <p class="text-sm text-gray-600 font-medium">
                  <span class="text-indigo-600">Click to upload</span> or drag and drop
                </p>
                <p class="text-xs text-gray-400">PDF files only</p>
              </div>
            </div>

            <!-- URL input -->
            <div class="flex gap-2">
              <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.07a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.57" />
                </svg>
                <input
                  v-model="urlInput"
                  type="url"
                  :disabled="addingUrl"
                  placeholder="Paste a URL to a medical article..."
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-shadow disabled:bg-gray-50 disabled:text-gray-400"
                  @keyup.enter="addUrl"
                />
              </div>
              <button
                :disabled="addingUrl || !urlInput?.trim()"
                class="px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-xl font-medium hover:bg-indigo-700 transition-colors disabled:opacity-40 shrink-0"
                @click="addUrl"
              >
                {{ addingUrl ? 'Adding...' : 'Add' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Loading state -->
        <div v-if="loadingLibrary" class="text-center py-12 text-gray-400">
          Loading your library...
        </div>

        <!-- Items List -->
        <template v-else-if="libraryItems.length">
          <div
            v-for="item in libraryItems"
            :key="item.id"
            class="rounded-2xl border overflow-hidden transition-all duration-500"
            :class="isProcessing(item.processing_status)
              ? 'bg-indigo-50/40 border-indigo-200 animate-pulse-slow shadow-sm shadow-indigo-100'
              : item.processing_status === 'failed'
                ? 'bg-white border-red-200'
                : 'bg-white border-gray-200'"
          >
            <!-- Item Header -->
            <div
              class="p-4 flex items-start gap-3 cursor-pointer"
              :class="item.processing_status === 'completed' ? 'hover:bg-gray-50/50' : ''"
              @click="item.processing_status === 'completed' && toggleExpanded(item.id)"
            >
              <!-- Source Icon -->
              <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="item.source_type === 'pdf_upload' ? 'bg-red-50' : 'bg-blue-50'">
                <!-- PDF icon -->
                <svg v-if="item.source_type === 'pdf_upload'" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <!-- Globe/web icon -->
                <svg v-else class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
              </div>

              <!-- Item Info -->
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <p class="font-medium text-gray-900 text-sm truncate">{{ item.title || 'Untitled document' }}</p>
                  <!-- Status Badge -->
                  <span
                    class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full"
                    :class="{
                      'bg-gray-100 text-gray-500': item.processing_status === 'pending',
                      'bg-blue-100 text-blue-600 animate-pulse': isProcessing(item.processing_status),
                      'bg-emerald-100 text-emerald-700': item.processing_status === 'completed',
                      'bg-red-100 text-red-600': item.processing_status === 'failed',
                    }"
                  >
                    {{ getStatusLabel(item.processing_status) }}
                  </span>
                </div>
                <!-- Topics tags -->
                <div v-if="item.ai_analysis?.categories?.medical_topics?.length" class="flex flex-wrap gap-1 mt-1.5">
                  <span
                    v-for="(topic, ti) in item.ai_analysis.categories.medical_topics.slice(0, 5)"
                    :key="ti"
                    class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full"
                  >{{ topic }}</span>
                </div>
                <!-- Error message + delete for failed items -->
                <div v-if="item.processing_status === 'failed'" class="flex items-center gap-2 mt-1">
                  <p v-if="item.processing_error" class="text-xs text-red-500 truncate">
                    {{ item.processing_error }}
                  </p>
                  <button
                    class="shrink-0 text-[10px] text-red-500 hover:text-red-600 font-medium px-2 py-0.5 rounded hover:bg-red-50 transition-colors"
                    @click.stop="deleteLibraryItem(item.id)"
                  >Remove</button>
                </div>
              </div>

              <!-- Expand chevron (completed items only) -->
              <svg
                v-if="item.processing_status === 'completed'"
                class="w-5 h-5 text-gray-400 shrink-0 transition-transform"
                :class="expandedItems[item.id] ? 'rotate-180' : ''"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>
            </div>

            <!-- Pipeline Progress (processing items) -->
            <div v-if="isProcessing(item.processing_status)" class="px-4 pb-4">
              <div class="flex items-center gap-1">
                <template v-for="(step, si) in pipelineSteps" :key="step.key">
                  <!-- Step dot -->
                  <div class="flex flex-col items-center">
                    <div
                      class="w-3 h-3 rounded-full transition-all"
                      :class="{
                        'bg-emerald-500': getStatusStep(item.processing_status) > si + 1,
                        'bg-indigo-500 animate-pulse ring-4 ring-indigo-100': getStatusStep(item.processing_status) === si + 1,
                        'bg-gray-200': getStatusStep(item.processing_status) < si + 1,
                      }"
                    />
                    <span
                      class="text-[9px] mt-1 whitespace-nowrap"
                      :class="{
                        'text-emerald-600 font-medium': getStatusStep(item.processing_status) > si + 1,
                        'text-indigo-600 font-semibold': getStatusStep(item.processing_status) === si + 1,
                        'text-gray-400': getStatusStep(item.processing_status) < si + 1,
                      }"
                    >{{ step.label }}</span>
                  </div>
                  <!-- Connector line -->
                  <div
                    v-if="si < pipelineSteps.length - 1"
                    class="flex-1 h-0.5 mb-4"
                    :class="getStatusStep(item.processing_status) > si + 1 ? 'bg-emerald-300' : 'bg-gray-200'"
                  />
                </template>
              </div>
            </div>

            <!-- Expanded View (completed items) -->
            <div v-if="item.processing_status === 'completed' && expandedItems[item.id]" class="border-t border-gray-100">
              <div class="p-4 space-y-4">
                <!-- Title + Evidence Level -->
                <div class="flex items-center gap-2">
                  <h3 class="font-semibold text-gray-900">{{ item.title }}</h3>
                  <span
                    v-if="item.ai_analysis?.categories?.evidence_level"
                    class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                    :class="getEvidenceBadgeClasses(item.ai_analysis.categories.evidence_level)"
                  >
                    Evidence {{ item.ai_analysis.categories.evidence_level }}
                  </span>
                </div>

                <!-- Summary -->
                <p v-if="item.ai_analysis?.summary" class="text-sm text-gray-700 leading-relaxed">
                  {{ item.ai_analysis.summary }}
                </p>

                <!-- Key Findings -->
                <div v-if="item.ai_analysis?.key_findings?.length">
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Key Findings</p>
                  <ul class="space-y-1">
                    <li
                      v-for="(finding, fi) in item.ai_analysis.key_findings"
                      :key="fi"
                      class="flex items-start gap-2 text-sm text-gray-700"
                    >
                      <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>{{ finding }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Relevance to You -->
                <div v-if="item.ai_analysis?.patient_relevance?.relevance_explanation" class="bg-indigo-50/50 rounded-xl p-3 border border-indigo-100">
                  <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide mb-1.5">Relevance to You</p>
                  <p class="text-sm text-indigo-900/80 leading-relaxed">{{ item.ai_analysis.patient_relevance.relevance_explanation }}</p>
                  <div v-if="item.ai_analysis?.patient_relevance?.matching_conditions?.length || item.ai_analysis?.patient_relevance?.matching_medications?.length" class="flex flex-wrap gap-1.5 mt-2">
                    <span
                      v-for="(mc, mci) in (item.ai_analysis.patient_relevance.matching_conditions || [])"
                      :key="'cond-' + mci"
                      class="text-[10px] font-medium bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full"
                    >{{ mc }}</span>
                    <span
                      v-for="(mm, mmi) in (item.ai_analysis.patient_relevance.matching_medications || [])"
                      :key="'med-' + mmi"
                      class="text-[10px] font-medium bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full"
                    >{{ mm }}</span>
                  </div>
                </div>

                <!-- Actionable Insights -->
                <div v-if="item.ai_analysis?.patient_relevance?.actionable_insights?.length">
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Actionable Insights</p>
                  <ul class="space-y-1">
                    <li
                      v-for="(insight, ii) in item.ai_analysis.patient_relevance.actionable_insights"
                      :key="ii"
                      class="flex items-start gap-2 text-sm text-gray-700"
                    >
                      <svg class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                      </svg>
                      <span>{{ insight }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Topics -->
                <div v-if="item.ai_analysis?.categories?.medical_topics?.length" class="flex flex-wrap gap-1.5">
                  <span
                    v-for="(topic, ti) in item.ai_analysis.categories.medical_topics"
                    :key="ti"
                    class="text-[11px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"
                  >{{ topic }}</span>
                </div>

                <!-- Analysis disclaimer -->
                <p class="text-[11px] text-amber-600 bg-amber-50 rounded-lg px-2.5 py-1.5 leading-relaxed">
                  This analysis was generated by AI and may contain errors. Always verify with your healthcare provider before acting on this information.
                </p>

                <!-- Delete button -->
                <div class="flex justify-end pt-2 border-t border-gray-100">
                  <button
                    class="flex items-center gap-1.5 text-xs text-red-500 hover:text-red-600 font-medium px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors"
                    @click.stop="deleteLibraryItem(item.id)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Delete from library
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Empty state -->
        <div v-else class="bg-white rounded-2xl border border-gray-200 p-8 text-center space-y-2">
          <svg class="w-12 h-12 text-gray-200 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
          </svg>
          <p class="text-gray-500 text-sm">Your personal medical library is empty.</p>
          <p class="text-gray-400 text-xs">Upload a PDF or add a URL to get started. AI will analyze the content and relate it to your health profile.</p>
        </div>

        <!-- Disclaimer -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-700 space-y-1">
          <p class="font-semibold">Important disclaimer</p>
          <p>Content found on the internet may not be accurate or applicable to your situation. AI-generated analysis can contain errors. Always consult your doctor or local health authority before making any medical decisions based on information from this library.</p>
          <p class="text-amber-600">Documents stored for personal medical reference only. Content is analyzed by AI and not redistributed.</p>
        </div>
      </div>
    </div>
  </PatientLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useApi } from '@/composables/useApi';
import { useAuthStore } from '@/stores/auth';
import PatientLayout from '@/layouts/PatientLayout.vue';
import type { LibraryItem, LibraryProcessingStatus, PipelineStep } from '@/types/library';
import type {
    Condition, Prescription, Reference, ConditionMatch,
    OeResult, OeSource, DrugLabelResult, SearchResultItem,
} from '@/types/medical';

interface ConditionLookup {
    loading: boolean;
    results: ConditionMatch[];
}

interface DrugLabelLookup {
    loading: boolean;
    result: DrugLabelResult | null;
}

interface SuggestedQuestion {
    question: string;
    context: string;
}

interface Tab {
    id: string;
    label: string;
}

const api = useApi();
const auth = useAuthStore();

const tabs: Tab[] = [
    { id: 'relevant', label: 'Relevant for You' },
    { id: 'ask', label: 'Ask (EBM)' },
    { id: 'search', label: 'Search Databases' },
    { id: 'my-library', label: 'My Library' },
];
const activeTab = ref<string>('relevant');

// --- Relevant for You ---
const loadingRelevant = ref<boolean>(true);
const conditions = ref<Condition[]>([]);
const medications = ref<Prescription[]>([]);
const references = ref<Reference[]>([]);
const conditionLookups = reactive<Record<string, ConditionLookup>>({});
const drugLabels = reactive<Record<string, DrugLabelLookup>>({});

async function loadRelevantData(): Promise<void> {
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

async function searchCondition(name: string): Promise<void> {
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

async function lookupDrugLabel(rx: Prescription): Promise<void> {
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

// --- Ask (OpenEvidence mockup) ---
const oeQuery = ref<string>('');
const oeLoading = ref<boolean>(false);
const oeResult = ref<OeResult | null>(null);

const suggestedQuestions = computed<SuggestedQuestion[]>(() => {
    const questions: SuggestedQuestion[] = [];
    if (conditions.value.length) {
        const cond = conditions.value[0];
        questions.push({
            question: `What are the latest guidelines for managing ${cond.code_display}?`,
            context: 'Based on your diagnosis',
        });
    }
    if (medications.value.length) {
        const rx = medications.value[0];
        const name = rx.medication?.display_name || rx.medication?.generic_name;
        questions.push({
            question: `What are the common side effects of ${name} I should watch for?`,
            context: 'Based on your current medication',
        });
        if (conditions.value.length) {
            questions.push({
                question: `Is ${name} first-line therapy for ${conditions.value[0].code_display}?`,
                context: 'Cross-referencing your medication and diagnosis',
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

const mockAnswers: Record<string, OeResult> = {
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

async function askOpenEvidence(): Promise<void> {
    if (!oeQuery.value?.trim()) return;
    oeLoading.value = true;
    oeResult.value = null;

    // Simulate API call delay
    await new Promise<void>(resolve => setTimeout(resolve, 2500));

    oeResult.value = { ...mockAnswers.default };
    oeLoading.value = false;
}

// --- Search tab ---
const searchQuery = ref<string>('');
const searchType = ref<string>('conditions');
const searchLoading = ref<boolean>(false);
const searchResults = ref<SearchResultItem[]>([]);
const searchDrugLabelResult = ref<DrugLabelResult | null>(null);
const searchSearched = ref<boolean>(false);

const searchSourceLabels: Record<string, string> = {
    conditions: 'NIH Clinical Tables (ICD-10)',
    drugs: 'NLM RxTerms',
    procedures: 'HCPCS',
    'drug-label': 'DailyMed',
};

const searchSourceLabel = computed<string | undefined>(() => searchSourceLabels[searchType.value]);

async function runSearch(): Promise<void> {
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

// --- My Library ---
const libraryItems = ref<LibraryItem[]>([]);
const loadingLibrary = ref<boolean>(false);
const uploadingFile = ref<boolean>(false);
const addingUrl = ref<boolean>(false);
const urlInput = ref<string>('');
const expandedItems = reactive<Record<string, boolean>>({});
const pollingIntervals: Record<string, ReturnType<typeof setInterval>> = {};
const dragOver = ref<boolean>(false);
const fileInput = ref<HTMLInputElement | null>(null);

const pipelineSteps: PipelineStep[] = [
    { key: 'extracting_text', label: 'Extracting text' },
    { key: 'analyzing', label: 'Analyzing' },
    { key: 'categorizing', label: 'Categorizing' },
    { key: 'relating', label: 'Relating to your health' },
    { key: 'verifying', label: 'Verifying' },
];

async function loadLibraryItems(): Promise<void> {
    loadingLibrary.value = true;
    try {
        const { data } = await api.get('/library');
        libraryItems.value = data.data?.data || data.data || [];
        libraryItems.value.forEach((item: LibraryItem) => {
            if (!(['completed', 'failed'] as string[]).includes(item.processing_status)) {
                startPolling(item.id);
            }
        });
    } catch {
        // Non-blocking
    } finally {
        loadingLibrary.value = false;
    }
}

async function uploadPdf(event: Event | { target: { files: File[]; value?: string } }): Promise<void> {
    const target = (event as { target: { files?: FileList | File[]; value?: string } }).target;
    const file = target.files?.[0];
    if (!file) return;
    uploadingFile.value = true;
    try {
        const form = new FormData();
        form.append('file', file);
        form.append('title', file.name.replace('.pdf', ''));
        const { data } = await api.post('/library/upload', form);
        const item: LibraryItem = data.data;
        libraryItems.value.unshift(item);
        startPolling(item.id);
    } catch (err) {
        console.error('Upload failed:', err);
    } finally {
        uploadingFile.value = false;
        if ('value' in target) target.value = '';
    }
}

function handleDrop(event: DragEvent): void {
    dragOver.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file && file.type === 'application/pdf') {
        uploadPdf({ target: { files: [file] } });
    }
}

async function addUrl(): Promise<void> {
    const url = urlInput.value?.trim();
    if (!url) return;
    addingUrl.value = true;
    try {
        const { data } = await api.post('/library/url', { url });
        const item: LibraryItem = data.data;
        libraryItems.value.unshift(item);
        startPolling(item.id);
        urlInput.value = '';
    } catch (err) {
        console.error('URL add failed:', err);
    } finally {
        addingUrl.value = false;
    }
}

function startPolling(itemId: string): void {
    if (pollingIntervals[itemId]) return;
    pollingIntervals[itemId] = setInterval(async () => {
        try {
            const { data } = await api.get(`/library/${itemId}/status`);
            const statusData = data.data as { processing_status: LibraryProcessingStatus; processing_error: string | null };
            const item = libraryItems.value.find(i => i.id === itemId);
            if (item) {
                item.processing_status = statusData.processing_status;
                item.processing_error = statusData.processing_error;
            }
            if (['completed', 'failed'].includes(statusData.processing_status)) {
                clearInterval(pollingIntervals[itemId]);
                delete pollingIntervals[itemId];
                if (statusData.processing_status === 'completed') {
                    const full = await api.get(`/library/${itemId}`);
                    if (item) Object.assign(item, full.data.data);
                }
            }
        } catch {
            clearInterval(pollingIntervals[itemId]);
            delete pollingIntervals[itemId];
        }
    }, 3000);
}

async function deleteLibraryItem(itemId: string): Promise<void> {
    try {
        await api.delete(`/library/${itemId}`);
        libraryItems.value = libraryItems.value.filter(i => i.id !== itemId);
        delete expandedItems[itemId];
    } catch (err) {
        console.error('Delete failed:', err);
    }
}

function toggleExpanded(itemId: string): void {
    expandedItems[itemId] = !expandedItems[itemId];
}

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    extracting_text: 'Extracting text',
    analyzing: 'Analyzing',
    categorizing: 'Categorizing',
    relating: 'Relating to your health',
    verifying: 'Verifying',
    completed: 'Completed',
    failed: 'Failed',
};

function getStatusLabel(status: string): string {
    return statusLabels[status] || status;
}

const statusStepMap: Record<string, number> = {
    extracting_text: 1,
    analyzing: 2,
    categorizing: 3,
    relating: 4,
    verifying: 5,
};

function getStatusStep(status: string): number {
    return statusStepMap[status] || 0;
}

const processingStatuses: string[] = ['extracting_text', 'analyzing', 'categorizing', 'relating', 'verifying'];

function isProcessing(status: string): boolean {
    return processingStatuses.includes(status);
}

function getEvidenceBadgeClasses(level: string | undefined): string {
    if (!level) return 'bg-gray-100 text-gray-600';
    const letter = level.charAt(0).toUpperCase();
    if (letter === 'A') return 'bg-emerald-100 text-emerald-700';
    if (letter === 'B') return 'bg-blue-100 text-blue-700';
    return 'bg-amber-100 text-amber-700';
}

onMounted(() => {
    loadRelevantData();
    loadLibraryItems();
});

onUnmounted(() => {
    Object.keys(pollingIntervals).forEach(id => {
        clearInterval(pollingIntervals[id]);
        delete pollingIntervals[id];
    });
});
</script>

<style scoped>
.animate-pulse-slow {
  animation: pulse-border 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse-border {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.15);
  }
  50% {
    box-shadow: 0 0 0 6px rgba(99, 102, 241, 0.05);
  }
}
</style>
