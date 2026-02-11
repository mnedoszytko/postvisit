<template>
  <PatientLayout>
    <div class="relative">
      <!-- Visit header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Visit Summary</h1>
        <div v-if="visit" class="flex flex-wrap items-center gap-2 mt-2">
          <span class="inline-flex items-center gap-1.5 text-sm text-gray-600">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            {{ formatDate(visit.started_at) }}
          </span>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
            {{ formatVisitType(visit.visit_type) }}
          </span>
          <span v-if="visit.practitioner" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
            <span v-if="visit.practitioner.primary_specialty" class="text-blue-500">&middot; {{ visit.practitioner.primary_specialty }}</span>
          </span>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="visitStore.loading" class="text-center py-12 text-gray-400">
        Loading visit data...
      </div>

      <!-- Empty visit — no transcript or SOAP note yet -->
      <div v-else-if="visit && isEmptyVisit" class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center space-y-4">
          <div class="w-16 h-16 mx-auto bg-emerald-50 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-800">No recording yet</h3>
          <p class="text-gray-500 text-sm max-w-sm mx-auto">
            This visit doesn't have a transcript yet. Start recording to get a complete summary with AI-powered insights.
          </p>
          <router-link
            :to="{ path: '/scribe', query: { visitId: route.params.id } }"
            class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
            </svg>
            Start Recording
          </router-link>
        </div>

        <!-- Still show attachments on empty visits -->
        <VisitAttachments :visit-id="route.params.id" />
      </div>

      <!-- Visit sections (has content) -->
      <div v-else-if="visit" class="space-y-4">
        <!-- SOAP Note sections -->
        <VisitSection
          v-for="section in soapSections"
          :key="section.key"
          :title="section.title"
          :content="section.content"
          :terms="section.terms"
          :section-key="section.sectionKey"
          @explain="openChat(section.title)"
          @term-click="showTermPopover"
        />

        <!-- Observations / Test Results -->
        <div v-if="visit.observations?.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="obsExpanded = !obsExpanded">
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-gray-800">Test Results &amp; Observations</h3>
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                {{ visit.observations.length }}
              </span>
            </div>
            <span class="text-gray-400 text-sm">{{ obsExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="obsExpanded" class="px-4 pb-4">
            <LabResults :observations="visit.observations" />
          </div>
        </div>

        <!-- Conditions / Diagnosis -->
        <div v-if="visit.conditions?.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="condExpanded = !condExpanded">
            <h3 class="font-semibold text-gray-800">Diagnosis</h3>
            <span class="text-gray-400 text-sm">{{ condExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="condExpanded" class="px-4 pb-4 space-y-2">
            <div v-for="cond in visit.conditions" :key="cond.id" class="flex items-start gap-3">
              <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full mt-0.5">{{ cond.code }}</span>
              <div>
                <p class="font-medium text-gray-800 text-sm">{{ cond.code_display }}</p>
                <p v-if="cond.clinical_notes" class="text-xs text-gray-500 mt-0.5">{{ cond.clinical_notes }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Prescriptions -->
        <div v-if="visit.prescriptions?.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="rxExpanded = !rxExpanded">
            <h3 class="font-semibold text-gray-800">Medications Prescribed</h3>
            <span class="text-gray-400 text-sm">{{ rxExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="rxExpanded" class="px-4 pb-4 space-y-3">
            <div v-for="rx in visit.prescriptions" :key="rx.id" class="border-b border-gray-100 pb-2 last:border-0">
              <p class="font-medium text-gray-800 text-sm">{{ rx.medication?.display_name || rx.medication?.generic_name }}</p>
              <p class="text-sm text-gray-600">{{ parseFloat(rx.dose_quantity) }} {{ rx.dose_unit }} &middot; {{ rx.frequency_text || rx.frequency }}</p>
              <p v-if="rx.special_instructions" class="text-xs text-gray-500 mt-1">{{ rx.special_instructions }}</p>
            </div>
          </div>
        </div>

        <!-- Patient Attachments -->
        <VisitAttachments :visit-id="route.params.id" />

        <!-- AI-Extracted Entities (from transcript analysis) -->
        <div v-if="entities && Object.keys(entities).length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="entitiesExpanded = !entitiesExpanded">
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-gray-800">AI-Extracted Clinical Entities</h3>
              <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">AI</span>
            </div>
            <span class="text-gray-400 text-sm">{{ entitiesExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="entitiesExpanded" class="px-4 pb-4 space-y-5">
            <div v-for="(items, category) in entities" :key="category">
              <!-- Medications: structured cards -->
              <template v-if="category === 'medications' && Array.isArray(items) && items.length > 0">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Medications</h4>
                <div class="space-y-2">
                  <div
                    v-for="(med, idx) in items"
                    :key="idx"
                    class="flex items-start gap-3 rounded-lg border border-gray-100 p-2.5"
                  >
                    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-blue-500" />
                    <div class="min-w-0">
                      <p class="font-medium text-gray-800 text-sm">{{ formatMedName(med) }}</p>
                      <p v-if="formatMedDetails(med)" class="text-xs text-gray-500 mt-0.5">{{ formatMedDetails(med) }}</p>
                      <span
                        v-if="getMedStatus(med)"
                        :class="medStatusClass(getMedStatus(med))"
                        class="inline-block text-[10px] font-medium px-1.5 py-0.5 rounded-full mt-1"
                      >{{ getMedStatus(med) }}</span>
                    </div>
                  </div>
                </div>
              </template>

              <!-- Test Results: structured rows -->
              <template v-else-if="category === 'test_results' && Array.isArray(items) && items.length > 0">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Test Results</h4>
                <div class="space-y-2">
                  <div
                    v-for="(result, idx) in items"
                    :key="idx"
                    class="rounded-lg border border-gray-100 p-2.5"
                  >
                    <template v-if="parseTestResult(result)">
                      <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-800 text-sm">{{ parseTestResult(result).test }}</span>
                        <span v-if="parseTestResult(result).date" class="text-[10px] text-gray-400">{{ parseTestResult(result).date }}</span>
                      </div>
                      <p class="text-sm text-gray-600 mt-0.5">{{ parseTestResult(result).result }}</p>
                    </template>
                    <template v-else>
                      <span class="text-sm text-gray-700">{{ cleanUnclear(formatEntityItem(result)) }}</span>
                    </template>
                  </div>
                </div>
              </template>

              <!-- Vitals: key-value pairs -->
              <template v-else-if="typeof items === 'object' && !Array.isArray(items) && Object.keys(items).length > 0">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ formatEntityCategory(category) }}</h4>
                <div class="grid grid-cols-2 gap-2">
                  <div v-for="(val, key) in items" :key="key" class="rounded-lg border border-gray-100 p-2">
                    <span class="text-[10px] text-gray-400 uppercase">{{ key }}</span>
                    <p class="text-sm font-medium text-gray-800">{{ cleanUnclear(String(val)) }}</p>
                  </div>
                </div>
              </template>

              <!-- Generic arrays (symptoms, diagnoses, etc.) -->
              <template v-else-if="Array.isArray(items) && items.length > 0">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ formatEntityCategory(category) }}</h4>
                <div class="space-y-1.5">
                  <div
                    v-for="(item, idx) in items"
                    :key="idx"
                    class="flex items-start gap-2 text-sm"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0" :class="entityDotColor(category)" />
                    <span class="text-gray-700">{{ cleanUnclear(formatEntityItem(item)) }}</span>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- Raw Transcript -->
        <div v-if="visit.transcript?.raw_transcript" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="transcriptExpanded = !transcriptExpanded">
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-gray-800">Visit Transcript</h3>
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                {{ visit.transcript.processing_status }}
              </span>
            </div>
            <span class="text-gray-400 text-sm">{{ transcriptExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="transcriptExpanded" class="px-4 pb-4 max-h-96 overflow-y-auto">
            <div v-if="visit.transcript.diarized_transcript?.clean_text" class="text-sm leading-relaxed whitespace-pre-wrap space-y-1">
              <template v-for="(line, i) in visit.transcript.diarized_transcript.clean_text.split('\n')" :key="i">
                <p v-if="line.startsWith('Doctor:') || line.startsWith('Dr:')" class="text-gray-700">
                  <span class="font-semibold text-emerald-700">Doctor:</span>{{ line.replace(/^(Doctor|Dr):/, '') }}
                </p>
                <p v-else-if="line.startsWith('Patient:')" class="text-gray-700">
                  <span class="font-semibold text-blue-600">Patient:</span>{{ line.replace(/^Patient:/, '') }}
                </p>
                <p v-else-if="line.trim()" class="text-gray-600">{{ line }}</p>
              </template>
            </div>
            <p v-else class="text-gray-600 text-sm leading-relaxed whitespace-pre-wrap">{{ visit.transcript.raw_transcript }}</p>
          </div>
        </div>
      </div>

      <!-- Term Popover -->
      <TermPopover
        :visible="popoverVisible"
        :term="popoverTerm"
        :definition="popoverDefinition"
        :anchor-rect="popoverAnchorRect"
        @close="popoverVisible = false"
        @ask-more="(term) => openChat(term)"
      />

      <!-- Chat Panel -->
      <ChatPanel
        v-if="chatOpen"
        :visit-id="route.params.id"
        :initial-context="chatContext"
        :highlight="chatHighlight"
        @close="chatOpen = false"
      />

      <!-- Floating chat button -->
      <button
        v-if="!chatOpen"
        class="fixed bottom-6 right-6 w-14 h-14 bg-emerald-600 text-white rounded-full shadow-lg hover:bg-emerald-700 transition-colors flex items-center justify-center text-xl"
        @click="openChat()"
      >
        ?
      </button>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useVisitStore } from '@/stores/visit';
import PatientLayout from '@/layouts/PatientLayout.vue';
import VisitSection from '@/components/VisitSection.vue';
import LabResults from '@/components/LabResults.vue';
import ChatPanel from '@/components/ChatPanel.vue';
import TermPopover from '@/components/TermPopover.vue';
import VisitAttachments from '@/components/VisitAttachments.vue';

const route = useRoute();
const visitStore = useVisitStore();
const chatOpen = ref(false);
const chatContext = ref('');
const chatHighlight = ref(false);
const obsExpanded = ref(false);
const condExpanded = ref(false);
const rxExpanded = ref(false);
const entitiesExpanded = ref(false);
const transcriptExpanded = ref(false);

// Term popover state
const popoverVisible = ref(false);
const popoverTerm = ref('');
const popoverDefinition = ref('');
const popoverAnchorRect = ref(null);

const visit = computed(() => visitStore.currentVisit);

const isEmptyVisit = computed(() => {
    const v = visit.value;
    if (!v) return false;
    return !v.visit_note && !v.transcript?.raw_transcript;
});

const entities = computed(() => visit.value?.transcript?.entities_extracted || null);

const sectionFieldMap = {
    cc: 'chief_complaint',
    hpi: 'history_of_present_illness',
    ros: 'review_of_systems',
    pe: 'physical_exam',
    assessment: 'assessment',
    plan: 'plan',
    followup: 'follow_up',
};

const soapSections = computed(() => {
    const note = visit.value?.visit_note;
    if (!note) return [];
    return [
        { key: 'cc', title: 'Chief Complaint' },
        { key: 'hpi', title: 'History of Present Illness' },
        { key: 'ros', title: 'Reported Symptoms' },
        { key: 'pe', title: 'Physical Examination' },
        { key: 'assessment', title: 'Assessment' },
        { key: 'plan', title: 'Plan' },
        { key: 'followup', title: 'Follow-up' },
    ].map(s => ({
        ...s,
        sectionKey: sectionFieldMap[s.key],
        content: note[sectionFieldMap[s.key]],
        terms: note.medical_terms?.[sectionFieldMap[s.key]] || [],
    })).filter(s => s.content);
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatVisitType(type) {
    if (!type) return 'Visit';
    return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function formatQuantity(val) {
    const num = parseFloat(val);
    if (isNaN(num)) return val;
    return Number.isInteger(num) ? num.toString() : parseFloat(num.toFixed(2)).toString();
}

const entityCategoryLabels = {
    symptoms: 'Symptoms',
    diagnoses: 'Diagnoses',
    medications: 'Medications',
    tests_ordered: 'Tests Ordered',
    test_results: 'Test Results',
    vitals: 'Vitals',
    allergies: 'Allergies',
    procedures: 'Procedures',
};

function formatEntityCategory(key) {
    return entityCategoryLabels[key] || key.replace(/_/g, ' ');
}

function formatEntityItem(item) {
    if (typeof item === 'string') return item;
    if (typeof item === 'object' && item !== null) {
        // Medications have name, dose, frequency, etc.
        if (item.name) {
            const parts = [item.name];
            if (item.dose) parts.push(item.dose);
            if (item.frequency) parts.push(item.frequency);
            if (item.route) parts.push(`(${item.route})`);
            if (item.status) parts.push(`[${item.status}]`);
            return parts.join(' — ');
        }
        if (item.description) return item.description;
        return JSON.stringify(item);
    }
    return String(item);
}

function entityDotColor(category) {
    const colors = {
        symptoms: 'bg-red-400',
        diagnoses: 'bg-amber-500',
        medications: 'bg-blue-500',
        tests_ordered: 'bg-purple-500',
        test_results: 'bg-purple-400',
        vitals: 'bg-emerald-500',
        allergies: 'bg-orange-500',
        procedures: 'bg-indigo-500',
    };
    return colors[category] || 'bg-gray-400';
}

function cleanUnclear(text) {
    if (!text) return '';
    return text.replace(/\[UNCLEAR\]/gi, '').trim() || 'Not specified';
}

function formatMedName(med) {
    if (typeof med === 'string') return cleanUnclear(med);
    return cleanUnclear(med?.name || '');
}

function formatMedDetails(med) {
    if (typeof med !== 'object' || !med) return '';
    const parts = [];
    if (med.dose && !/^\[UNCLEAR\]$/i.test(med.dose)) parts.push(med.dose);
    if (med.frequency && !/^\[UNCLEAR\]$/i.test(med.frequency)) parts.push(med.frequency);
    if (med.route && !/^\[UNCLEAR\]$/i.test(med.route)) parts.push(med.route);
    return parts.join(' · ');
}

function getMedStatus(med) {
    if (typeof med !== 'object' || !med) return '';
    return med.status || '';
}

function medStatusClass(status) {
    const classes = {
        new: 'bg-green-100 text-green-700',
        continued: 'bg-blue-100 text-blue-700',
        changed: 'bg-amber-100 text-amber-700',
        discontinued: 'bg-gray-200 text-gray-500',
    };
    return classes[status] || 'bg-gray-100 text-gray-600';
}

function parseTestResult(result) {
    if (typeof result === 'string') {
        return { test: result, result: '', date: '' };
    }
    if (typeof result === 'object' && result !== null) {
        return {
            test: result.test || result.name || result.code || '',
            result: result.result || result.value || '',
            date: result.date || '',
        };
    }
    return null;
}

function showTermPopover(payload) {
    popoverTerm.value = payload.term;
    popoverDefinition.value = payload.definition;
    popoverAnchorRect.value = payload.anchorRect;
    popoverVisible.value = true;
}

function openChat(context = '') {
    popoverVisible.value = false;
    if (chatOpen.value) {
        // Chat already open — trigger highlight animation to signal context change
        chatHighlight.value = true;
        setTimeout(() => { chatHighlight.value = false; }, 600);
    }
    chatContext.value = context;
    chatOpen.value = true;
}

onMounted(() => {
    visitStore.fetchVisit(route.params.id);
});
</script>
