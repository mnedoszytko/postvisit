<template>
  <PatientLayout>
    <div class="relative">
      <!-- Visit header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Visit Summary</h1>
        <p v-if="visit" class="text-gray-500">
          {{ formatDate(visit.started_at) }} &middot; {{ formatVisitType(visit.visit_type) }}
          <span v-if="visit.practitioner">
            &middot; Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
          </span>
        </p>
        <p v-if="visit?.reason_for_visit" class="text-gray-600 mt-1">{{ visit.reason_for_visit }}</p>
      </div>

      <!-- Loading state -->
      <div v-if="visitStore.loading" class="text-center py-12 text-gray-400">
        Loading visit data...
      </div>

      <!-- Visit sections -->
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
            <h3 class="font-semibold text-gray-800">Test Results &amp; Observations</h3>
            <span class="text-gray-400 text-sm">{{ obsExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="obsExpanded" class="px-4 pb-4 space-y-3">
            <div v-for="obs in visit.observations" :key="obs.id" class="border-b border-gray-100 pb-2 last:border-0">
              <div class="flex items-center justify-between">
                <span class="font-medium text-gray-800 text-sm">{{ obs.code_display }}</span>
                <span
                  :class="['text-xs px-2 py-0.5 rounded-full', interpretationClass(obs.interpretation)]"
                >
                  {{ obs.interpretation === 'N' ? 'Normal' : obs.interpretation === 'H' ? 'High' : obs.interpretation === 'L' ? 'Low' : obs.interpretation || '' }}
                </span>
              </div>
              <p class="text-sm text-gray-600">
                <template v-if="obs.value_type === 'quantity'">{{ formatQuantity(obs.value_quantity) }} {{ obs.value_unit }}</template>
                <template v-else>{{ obs.value_string }}</template>
              </p>
              <p v-if="obs.reference_range_text" class="text-xs text-gray-400">Ref: {{ obs.reference_range_text }}</p>
            </div>
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

        <!-- AI-Extracted Entities (from transcript analysis) -->
        <div v-if="entities && Object.keys(entities).length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="entitiesExpanded = !entitiesExpanded">
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-gray-800">AI-Extracted Clinical Entities</h3>
              <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">AI</span>
            </div>
            <span class="text-gray-400 text-sm">{{ entitiesExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="entitiesExpanded" class="px-4 pb-4 space-y-4">
            <div v-for="(items, category) in entities" :key="category">
              <template v-if="Array.isArray(items) && items.length > 0">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ formatEntityCategory(category) }}</h4>
                <div class="space-y-1.5">
                  <div
                    v-for="(item, idx) in items"
                    :key="idx"
                    class="flex items-start gap-2 text-sm"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0" :class="entityDotColor(category)" />
                    <span class="text-gray-700">{{ formatEntityItem(item) }}</span>
                  </div>
                </div>
              </template>
              <template v-else-if="typeof items === 'object' && !Array.isArray(items) && Object.keys(items).length > 0">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ formatEntityCategory(category) }}</h4>
                <div class="space-y-1">
                  <div v-for="(val, key) in items" :key="key" class="flex items-center gap-2 text-sm">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="entityDotColor(category)" />
                    <span class="text-gray-500">{{ key }}:</span>
                    <span class="text-gray-700">{{ val }}</span>
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
          <div v-if="transcriptExpanded" class="px-4 pb-4">
            <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-wrap max-h-96 overflow-y-auto">{{ visit.transcript.raw_transcript }}</p>
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
import ChatPanel from '@/components/ChatPanel.vue';
import TermPopover from '@/components/TermPopover.vue';

const route = useRoute();
const visitStore = useVisitStore();
const chatOpen = ref(false);
const chatContext = ref('');
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
        { key: 'ros', title: 'Review of Systems' },
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

function interpretationClass(interp) {
    if (interp === 'N') return 'bg-green-100 text-green-700';
    if (interp === 'H') return 'bg-red-100 text-red-700';
    if (interp === 'L') return 'bg-blue-100 text-blue-700';
    return 'bg-gray-100 text-gray-600';
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

function showTermPopover(payload) {
    popoverTerm.value = payload.term;
    popoverDefinition.value = payload.definition;
    popoverAnchorRect.value = payload.anchorRect;
    popoverVisible.value = true;
}

function openChat(context = '') {
    popoverVisible.value = false;
    chatContext.value = context;
    chatOpen.value = true;
}

onMounted(() => {
    visitStore.fetchVisit(route.params.id);
});
</script>
