<template>
  <PatientLayout>
    <div class="relative">
      <!-- Visit header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Visit Summary</h1>
        <p v-if="visit" class="text-gray-500">
          {{ formatDate(visit.started_at) }} &middot; {{ visit.visit_type }}
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
          @explain="openChat(section.title)"
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
                <template v-if="obs.value_type === 'quantity'">{{ obs.value_quantity }} {{ obs.value_unit }}</template>
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
      </div>

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

const route = useRoute();
const visitStore = useVisitStore();
const chatOpen = ref(false);
const chatContext = ref('');
const obsExpanded = ref(false);
const condExpanded = ref(false);
const rxExpanded = ref(false);

const visit = computed(() => visitStore.currentVisit);

const soapSections = computed(() => {
    const note = visit.value?.visit_note;
    if (!note) return [];
    return [
        { key: 'cc', title: 'Chief Complaint', content: note.chief_complaint },
        { key: 'hpi', title: 'History of Present Illness', content: note.history_of_present_illness },
        { key: 'ros', title: 'Review of Systems', content: note.review_of_systems },
        { key: 'pe', title: 'Physical Examination', content: note.physical_exam },
        { key: 'assessment', title: 'Assessment', content: note.assessment },
        { key: 'plan', title: 'Plan', content: note.plan },
        { key: 'followup', title: 'Follow-up', content: note.follow_up },
    ].filter(s => s.content);
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function interpretationClass(interp) {
    if (interp === 'N') return 'bg-green-100 text-green-700';
    if (interp === 'H') return 'bg-red-100 text-red-700';
    if (interp === 'L') return 'bg-blue-100 text-blue-700';
    return 'bg-gray-100 text-gray-600';
}

function openChat(context = '') {
    chatContext.value = context;
    chatOpen.value = true;
}

onMounted(() => {
    visitStore.fetchVisit(route.params.id);
});
</script>
