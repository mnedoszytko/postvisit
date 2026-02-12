<template>
  <DoctorLayout>
    <div class="space-y-6">
      <div class="flex items-center gap-3">
        <router-link :to="`/doctor/patients/${route.params.patientId}`" class="text-sm text-emerald-600 hover:text-emerald-700">
          &larr; Back to Patient
        </router-link>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
        Loading visit data...
      </div>

      <template v-else-if="visit">
        <!-- Visit header -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                {{ formatVisitType(visit.visit_type) }}
              </h1>
              <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-gray-500">
                <span>{{ formatDateTime(visit.started_at) }}</span>
                <span v-if="visit.practitioner" class="text-gray-400">&middot;</span>
                <span v-if="visit.practitioner">
                  Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
                  <span v-if="visit.practitioner.primary_specialty" class="text-gray-400">({{ visit.practitioner.primary_specialty }})</span>
                </span>
              </div>
              <p v-if="visit.reason_for_visit" class="text-sm text-gray-600 mt-2">{{ visit.reason_for_visit }}</p>
            </div>
            <span
              :class="[
                'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold',
                visit.visit_status === 'completed' ? 'bg-emerald-100 text-emerald-700' :
                visit.visit_status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                'bg-gray-100 text-gray-600'
              ]"
            >
              {{ visit.visit_status || 'unknown' }}
            </span>
          </div>
        </div>

        <!-- SOAP Note -->
        <section v-if="visitNote">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">SOAP Note</h2>
          <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
            <div v-if="visitNote.chief_complaint" class="p-5">
              <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Chief Complaint</h3>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ visitNote.chief_complaint }}</p>
            </div>
            <div v-if="visitNote.history_of_present_illness" class="p-5">
              <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">History of Present Illness</h3>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ visitNote.history_of_present_illness }}</p>
            </div>
            <div v-if="visitNote.review_of_systems" class="p-5">
              <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Review of Systems</h3>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ visitNote.review_of_systems }}</p>
            </div>
            <div v-if="visitNote.physical_exam" class="p-5">
              <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Physical Exam</h3>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ visitNote.physical_exam }}</p>
            </div>
            <div v-if="visitNote.assessment" class="p-5">
              <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Assessment</h3>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ visitNote.assessment }}</p>
            </div>
            <div v-if="visitNote.plan" class="p-5">
              <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Plan</h3>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ visitNote.plan }}</p>
            </div>
            <div v-if="visitNote.follow_up" class="p-5">
              <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Follow-Up</h3>
              <p class="text-sm text-gray-800">
                {{ visitNote.follow_up }}
                <span v-if="visitNote.follow_up_timeframe" class="text-gray-400 ml-1">({{ visitNote.follow_up_timeframe }})</span>
              </p>
            </div>
          </div>
        </section>

        <!-- Medical Terms (from term extractor) -->
        <section v-if="medicalTerms.length > 0">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Medical Terms</h2>
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <th class="px-5 py-3">Term</th>
                  <th class="px-5 py-3 hidden sm:table-cell">Category</th>
                  <th class="px-5 py-3">Definition</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="(term, idx) in medicalTerms" :key="idx" class="text-sm">
                  <td class="px-5 py-3 font-medium text-gray-900 whitespace-nowrap">{{ term.term }}</td>
                  <td class="px-5 py-3 hidden sm:table-cell">
                    <span
                      v-if="term.category"
                      :class="[
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                        termCategoryClass(term.category)
                      ]"
                    >
                      {{ term.category }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-gray-600">{{ term.definition || term.explanation || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Conditions -->
        <section v-if="visit.conditions?.length > 0">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Conditions / Diagnoses</h2>
          <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-50">
            <div v-for="condition in visit.conditions" :key="condition.id" class="px-5 py-3 flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ condition.code_display }}</p>
                <p v-if="condition.code" class="text-xs text-gray-400">{{ condition.code_system }}: {{ condition.code }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span
                  v-if="condition.severity"
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                    condition.severity === 'severe' ? 'bg-red-100 text-red-700' :
                    condition.severity === 'moderate' ? 'bg-amber-100 text-amber-700' :
                    'bg-green-100 text-green-700'
                  ]"
                >
                  {{ condition.severity }}
                </span>
                <span
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                    condition.clinical_status === 'active' ? 'bg-blue-100 text-blue-700' :
                    'bg-gray-100 text-gray-600'
                  ]"
                >
                  {{ condition.clinical_status }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- Prescriptions -->
        <section v-if="visit.prescriptions?.length > 0">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Prescriptions</h2>
          <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-50">
            <div v-for="rx in visit.prescriptions" :key="rx.id" class="px-5 py-3">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-900">
                  {{ rx.medication?.display_name || rx.medication?.generic_name || 'Unknown' }}
                  <span v-if="rx.medication?.strength_value" class="text-gray-500 font-normal">
                    {{ rx.medication.strength_value }}{{ rx.medication.strength_unit }}
                  </span>
                </p>
                <span
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                    rx.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                    rx.status === 'completed' ? 'bg-gray-100 text-gray-600' :
                    'bg-amber-100 text-amber-700'
                  ]"
                >
                  {{ rx.status }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                {{ rx.dose_quantity }}{{ rx.dose_unit ? ' ' + rx.dose_unit : '' }}
                <span v-if="rx.frequency_text || rx.frequency"> &middot; {{ rx.frequency_text || rx.frequency }}</span>
                <span v-if="rx.route"> &middot; {{ rx.route }}</span>
              </p>
            </div>
          </div>
        </section>

        <!-- Observations (visit-specific) -->
        <section v-if="visit.observations?.length > 0">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Observations</h2>
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <th class="px-5 py-3">Observation</th>
                  <th class="px-5 py-3">Value</th>
                  <th class="px-5 py-3 hidden sm:table-cell">Date</th>
                  <th class="px-5 py-3 hidden md:table-cell">Category</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="obs in visit.observations" :key="obs.id" class="text-sm">
                  <td class="px-5 py-3 font-medium text-gray-900">{{ obs.code_display || obs.code }}</td>
                  <td class="px-5 py-3 text-gray-600">
                    {{ obs.value_quantity ?? obs.value_string ?? '—' }}
                    <span v-if="obs.value_unit" class="text-gray-400">{{ obs.value_unit }}</span>
                  </td>
                  <td class="px-5 py-3 text-gray-500 hidden sm:table-cell">{{ formatDate(obs.effective_date) }}</td>
                  <td class="px-5 py-3 text-gray-400 hidden md:table-cell">{{ obs.category }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Transcript (raw) -->
        <section v-if="visit.transcript">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Transcript</h2>
          <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <!-- Diarized transcript (speaker-separated) -->
            <div v-if="diarizedTranscript.length > 0" class="space-y-3">
              <div v-for="(segment, idx) in diarizedTranscript" :key="idx" class="flex gap-3">
                <span
                  :class="[
                    'shrink-0 w-16 text-xs font-semibold text-right pt-0.5',
                    segment.speaker?.toLowerCase().includes('doctor') || segment.speaker?.toLowerCase().includes('physician')
                      ? 'text-blue-600'
                      : 'text-emerald-600'
                  ]"
                >
                  {{ segment.speaker || 'Unknown' }}
                </span>
                <p class="text-sm text-gray-700 flex-1">{{ segment.text }}</p>
              </div>
            </div>
            <!-- Fallback: raw transcript text -->
            <p v-else class="text-sm text-gray-700 whitespace-pre-wrap">{{ visit.transcript.raw_transcript }}</p>
          </div>
        </section>

        <!-- Extracted Entities (from transcript) -->
        <section v-if="extractedEntities.length > 0">
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Extracted Entities</h2>
          <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(entity, idx) in extractedEntities"
                :key="idx"
                :class="[
                  'inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border',
                  entityTypeClass(entity.type || entity.category)
                ]"
              >
                <span v-if="entity.type || entity.category" class="opacity-60 mr-1.5">{{ entity.type || entity.category }}:</span>
                {{ entity.text || entity.term || entity.name || entity }}
              </span>
            </div>
          </div>
        </section>
      </template>

      <div v-else class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
        Visit not found.
      </div>
    </div>
  </DoctorLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
import DoctorLayout from '@/layouts/DoctorLayout.vue';

const route = useRoute();
const api = useApi();

const visit = ref(null);
const loading = ref(true);

const visitNote = computed(() => visit.value?.visit_note || null);

const medicalTerms = computed(() => {
    const terms = visitNote.value?.medical_terms;
    if (!terms) return [];
    return Array.isArray(terms) ? terms : [];
});

const diarizedTranscript = computed(() => {
    const dt = visit.value?.transcript?.diarized_transcript;
    if (!dt) return [];
    return Array.isArray(dt) ? dt : [];
});

const extractedEntities = computed(() => {
    const entities = visit.value?.transcript?.entities_extracted;
    if (!entities) return [];
    return Array.isArray(entities) ? entities : [];
});

onMounted(async () => {
    try {
        const res = await api.get(`/doctor/patients/${route.params.patientId}/visits/${route.params.visitId}`);
        visit.value = res.data.data;
    } catch {
        // Handled by API interceptor
    } finally {
        loading.value = false;
    }
});

function formatVisitType(type) {
    if (!type) return 'Visit';
    return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function formatDateTime(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function termCategoryClass(category) {
    const c = (category || '').toLowerCase();
    if (c.includes('diagnosis') || c.includes('condition')) return 'bg-red-50 text-red-700 border border-red-200';
    if (c.includes('medication') || c.includes('drug')) return 'bg-blue-50 text-blue-700 border border-blue-200';
    if (c.includes('procedure') || c.includes('treatment')) return 'bg-violet-50 text-violet-700 border border-violet-200';
    if (c.includes('anatomy') || c.includes('body')) return 'bg-amber-50 text-amber-700 border border-amber-200';
    if (c.includes('lab') || c.includes('test')) return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    return 'bg-gray-50 text-gray-700 border border-gray-200';
}

function entityTypeClass(type) {
    const t = (type || '').toLowerCase();
    if (t.includes('diagnosis') || t.includes('condition')) return 'bg-red-50 text-red-700 border-red-200';
    if (t.includes('medication') || t.includes('drug')) return 'bg-blue-50 text-blue-700 border-blue-200';
    if (t.includes('procedure')) return 'bg-violet-50 text-violet-700 border-violet-200';
    if (t.includes('symptom')) return 'bg-amber-50 text-amber-700 border-amber-200';
    if (t.includes('lab') || t.includes('vital')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    return 'bg-gray-50 text-gray-700 border-gray-200';
}
</script>
