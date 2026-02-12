<template>
  <DoctorLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Patients</h1>
        <input
          v-model="search"
          type="text"
          placeholder="Search patients..."
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-64"
          @input="handleSearch"
        />
      </div>

      <div v-if="doctorStore.loading" class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
        Loading patients...
      </div>

      <div v-else-if="doctorStore.patients.length === 0" class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
        No patients found.
      </div>

      <!-- Patient table -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <th class="px-5 py-3">Patient</th>
              <th class="px-5 py-3 hidden md:table-cell">Condition</th>
              <th class="px-5 py-3 hidden lg:table-cell">Last Vitals</th>
              <th class="px-5 py-3 hidden sm:table-cell">Visits</th>
              <th class="px-5 py-3 hidden sm:table-cell">Last Visit</th>
              <th class="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr
              v-for="patient in doctorStore.patients"
              :key="patient.id"
              class="hover:bg-gray-50/50 transition-colors"
            >
              <!-- Name + avatar -->
              <td class="px-5 py-4">
                <router-link :to="`/doctor/patients/${patient.id}`" class="flex items-center gap-3">
                  <img
                    v-if="patient.photo_url"
                    :src="patient.photo_url"
                    :alt="`${patient.first_name} ${patient.last_name}`"
                    class="w-10 h-10 rounded-full object-cover shrink-0"
                  />
                  <div
                    v-else
                    :class="[
                      'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0',
                      avatarClasses(patient)
                    ]"
                  >
                    {{ initials(patient) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-900 truncate">
                      {{ patient.first_name }} {{ patient.last_name }}
                    </p>
                    <p v-if="patient.age" class="text-xs text-gray-400">Age {{ patient.age }}</p>
                  </div>
                </router-link>
              </td>

              <!-- Condition -->
              <td class="px-5 py-4 hidden md:table-cell">
                <p v-if="patient.primary_condition" class="text-sm text-gray-600 truncate max-w-48">
                  {{ patient.primary_condition }}
                </p>
                <span v-else class="text-xs text-gray-300">&mdash;</span>
              </td>

              <!-- Last Vitals -->
              <td class="px-5 py-4 hidden lg:table-cell">
                <div v-if="patient.last_vitals" class="flex items-center gap-3 text-xs text-gray-600">
                  <span v-if="patient.last_vitals.bp" class="inline-flex items-center gap-1">
                    <svg class="w-3 h-3 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg>
                    {{ patient.last_vitals.bp }}
                  </span>
                  <span v-if="patient.last_vitals.weight" class="inline-flex items-center gap-1">
                    <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                    {{ formatWeight(patient.last_vitals.weight) }}
                  </span>
                </div>
                <span v-else class="text-xs text-gray-300">&mdash;</span>
              </td>

              <!-- Visits count -->
              <td class="px-5 py-4 hidden sm:table-cell">
                <span class="text-sm text-gray-600">{{ patient.visits_count || 0 }}</span>
              </td>

              <!-- Last visit -->
              <td class="px-5 py-4 hidden sm:table-cell">
                <span v-if="patient.last_visit_date" class="text-sm text-gray-500">{{ formatDate(patient.last_visit_date) }}</span>
                <span v-else class="text-xs text-gray-300">&mdash;</span>
              </td>

              <!-- Actions -->
              <td class="px-5 py-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    v-for="action in quickActions.slice(0, 2)"
                    :key="action.label"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs text-gray-500 hover:bg-emerald-50 hover:text-emerald-700 transition-colors whitespace-nowrap"
                    :title="action.label"
                    @click="handleQuickAction(action.label, patient)"
                  >
                    <span class="w-4 h-4 shrink-0" v-html="action.icon"></span>
                    <span class="hidden xl:inline">{{ action.short }}</span>
                  </button>
                  <router-link
                    :to="`/doctor/patients/${patient.id}`"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs text-gray-500 hover:bg-indigo-50 hover:text-indigo-700 transition-colors whitespace-nowrap"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <span class="hidden xl:inline">View</span>
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Action Modals -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="activeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal">
          <div class="fixed inset-0 bg-black/40" />
          <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
            <div class="flex items-center justify-between mb-5">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ activeModal === 'schedule_followup' ? 'Schedule Follow-up' : activeModal === 'renew_prescription' ? 'Renew Prescription' : activeModal === 'send_recommendation' ? 'Send Recommendation' : 'Request Labs' }}
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ modalPatient?.first_name }} {{ modalPatient?.last_name }}</p>
              </div>
              <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <!-- Schedule Follow-up -->
            <div v-if="activeModal === 'schedule_followup'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Timeframe</label>
                <div class="grid grid-cols-2 gap-2">
                  <button v-for="tf in ['1 week', '2 weeks', '1 month', '3 months']" :key="tf"
                    :class="['px-3 py-2 rounded-xl text-sm font-medium border transition-colors', followupTimeframe === tf ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'border-gray-200 text-gray-600 hover:border-emerald-300']"
                    @click="followupTimeframe = tf">
                    {{ tf }}
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                <textarea v-model="followupNote" rows="2" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" placeholder="Additional instructions..." />
              </div>
            </div>

            <!-- Renew Prescription -->
            <div v-if="activeModal === 'renew_prescription'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Medication</label>
                <div v-if="patientMedications.length" class="space-y-2 mb-2">
                  <button v-for="med in patientMedications" :key="med.id"
                    :class="['w-full text-left px-3 py-2.5 rounded-xl border transition-colors', prescriptionMed === med.name ? 'bg-emerald-50 border-emerald-500' : 'border-gray-200 hover:border-emerald-300']"
                    @click="prescriptionMed = med.name; showOtherMed = false">
                    <span class="text-sm font-medium text-gray-900">{{ med.name }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ med.dose }} &middot; {{ med.frequency }}</span>
                  </button>
                </div>
                <button v-if="!showOtherMed"
                  :class="['w-full text-left px-3 py-2.5 rounded-xl border transition-colors text-sm', showOtherMed || (!patientMedications.length) ? '' : 'border-dashed', prescriptionMed && !patientMedications.find(m => m.name === prescriptionMed) ? 'bg-emerald-50 border-emerald-500' : 'border-gray-200 hover:border-emerald-300 text-gray-500']"
                  @click="showOtherMed = true; prescriptionMed = ''">
                  + Other medication
                </button>
                <div v-if="showOtherMed" class="mt-2">
                  <input v-model="prescriptionMed" type="text" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" placeholder="Enter medication name..." autofocus />
                  <button v-if="patientMedications.length" class="text-xs text-gray-400 hover:text-gray-600 mt-1" @click="showOtherMed = false; prescriptionMed = ''">
                    &larr; Back to current medications
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                <div class="grid grid-cols-3 gap-2">
                  <button v-for="d in [30, 60, 90]" :key="d"
                    :class="['px-3 py-2 rounded-xl text-sm font-medium border transition-colors', prescriptionDays === d ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'border-gray-200 text-gray-600 hover:border-emerald-300']"
                    @click="prescriptionDays = d">
                    {{ d }} days
                  </button>
                </div>
              </div>
            </div>

            <!-- Send Recommendation -->
            <div v-if="activeModal === 'send_recommendation'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea v-model="recommendationMsg" rows="4" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" placeholder="Write your recommendation..." />
              </div>
            </div>

            <!-- Request Labs -->
            <div v-if="activeModal === 'request_labs'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lab Panel</label>
                <select v-model="selectedPanel" @change="applyLabPanel"
                  class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                  <option value="">Custom selection</option>
                  <option v-for="panel in labPanels" :key="panel.id" :value="panel.id">{{ panel.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Individual Tests</label>
                <div class="flex flex-wrap gap-2">
                  <button v-for="lab in allLabTests" :key="lab"
                    :class="['px-3 py-2 rounded-xl text-sm font-medium border transition-colors', selectedLabs.includes(lab) ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'border-gray-200 text-gray-600 hover:border-emerald-300']"
                    @click="toggleLab(lab); selectedPanel = ''">
                    {{ lab }}
                  </button>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
              <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                Cancel
              </button>
              <button @click="submitQuickAction" :disabled="submitting || !canSubmit"
                :class="['px-5 py-2 rounded-xl text-sm font-semibold text-white transition-colors', submitting || !canSubmit ? 'bg-gray-300 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700']">
                {{ submitting ? 'Sending...' : 'Submit' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </DoctorLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDoctorStore } from '@/stores/doctor';
import { useToastStore } from '@/stores/toast';
import { useApi } from '@/composables/useApi';
import DoctorLayout from '@/layouts/DoctorLayout.vue';

const doctorStore = useDoctorStore();
const toast = useToastStore();
const api = useApi();
const search = ref('');

// Modal state
const activeModal = ref(null);
const modalPatient = ref(null);
const submitting = ref(false);

// Form state
const followupTimeframe = ref('');
const followupNote = ref('');
const prescriptionMed = ref('');
const prescriptionDays = ref(null);
const recommendationMsg = ref('');
const selectedLabs = ref([]);
const showOtherMed = ref(false);
const selectedPanel = ref('');

const patientMedications = computed(() => modalPatient.value?.active_medications || []);

const allLabTests = ['CBC', 'BMP', 'CMP', 'Lipid Panel', 'HbA1c', 'TSH', 'T3/T4', 'BNP', 'Troponin', 'eGFR', 'Urinalysis', 'INR'];

const labPanels = [
    { id: 'cardiac', label: 'Cardiac Panel', tests: ['CBC', 'BMP', 'BNP', 'Troponin', 'Lipid Panel'] },
    { id: 'metabolic', label: 'Comprehensive Metabolic (CMP)', tests: ['CMP', 'CBC'] },
    { id: 'lipid', label: 'Lipid Panel', tests: ['Lipid Panel', 'HbA1c'] },
    { id: 'thyroid', label: 'Thyroid Panel', tests: ['TSH', 'T3/T4'] },
    { id: 'renal', label: 'Renal Panel', tests: ['BMP', 'eGFR', 'Urinalysis'] },
    { id: 'coag', label: 'Coagulation Panel', tests: ['CBC', 'INR'] },
];

const canSubmit = computed(() => {
    if (!activeModal.value) return false;
    if (activeModal.value === 'schedule_followup') return !!followupTimeframe.value;
    if (activeModal.value === 'renew_prescription') return !!prescriptionMed.value && !!prescriptionDays.value;
    if (activeModal.value === 'send_recommendation') return !!recommendationMsg.value.trim();
    if (activeModal.value === 'request_labs') return selectedLabs.value.length > 0;
    return false;
});

const quickActions = [
    {
        label: 'Schedule Follow-up',
        short: 'Follow-up',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
    },
    {
        label: 'Renew Prescription',
        short: 'Prescription',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>',
    },
    {
        label: 'Send Recommendation',
        short: 'Message',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>',
    },
    {
        label: 'Request Labs',
        short: 'Labs',
        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>',
    },
];

const ACTION_MAP = {
    'Schedule Follow-up': 'schedule_followup',
    'Renew Prescription': 'renew_prescription',
    'Send Recommendation': 'send_recommendation',
    'Request Labs': 'request_labs',
};

function handleQuickAction(label, patient) {
    const action = ACTION_MAP[label];
    if (!action) return;

    modalPatient.value = patient;
    activeModal.value = action;

    followupTimeframe.value = '';
    followupNote.value = '';
    prescriptionMed.value = '';
    prescriptionDays.value = null;
    showOtherMed.value = false;
    recommendationMsg.value = '';
    selectedLabs.value = [];
    selectedPanel.value = '';
}

function closeModal() {
    activeModal.value = null;
    modalPatient.value = null;
}

async function submitQuickAction() {
    if (!modalPatient.value || !activeModal.value) return;

    const payload = { action: activeModal.value };

    if (activeModal.value === 'schedule_followup') {
        if (!followupTimeframe.value) return;
        payload.timeframe = followupTimeframe.value;
        payload.note = followupNote.value || null;
    } else if (activeModal.value === 'renew_prescription') {
        if (!prescriptionMed.value || !prescriptionDays.value) return;
        payload.medication = prescriptionMed.value;
        payload.duration_days = prescriptionDays.value;
    } else if (activeModal.value === 'send_recommendation') {
        if (!recommendationMsg.value.trim()) return;
        payload.message = recommendationMsg.value;
    } else if (activeModal.value === 'request_labs') {
        if (selectedLabs.value.length === 0) return;
        payload.labs = selectedLabs.value;
    }

    if (modalPatient.value.latest_visit_id) {
        payload.visit_id = modalPatient.value.latest_visit_id;
    }

    submitting.value = true;
    try {
        await api.post(`/doctor/patients/${modalPatient.value.id}/quick-action`, payload);
        toast.success(`${activeModal.value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())} sent for ${modalPatient.value.first_name} ${modalPatient.value.last_name}`);
        closeModal();
    } catch (e) {
        // useApi interceptor handles error toasts
    } finally {
        submitting.value = false;
    }
}

function toggleLab(lab) {
    const idx = selectedLabs.value.indexOf(lab);
    if (idx >= 0) {
        selectedLabs.value.splice(idx, 1);
    } else {
        selectedLabs.value.push(lab);
    }
}

function applyLabPanel() {
    const panel = labPanels.find(p => p.id === selectedPanel.value);
    selectedLabs.value = panel ? [...panel.tests] : [];
}

let debounceTimer = null;
function handleSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        doctorStore.fetchPatients(search.value);
    }, 300);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatWeight(w) {
    if (!w) return '';
    const s = String(w);
    if (s.includes(' ')) return parseFloat(s) + s.replace(/[\d.]+/, '');
    return parseFloat(s) + ' kg';
}

function initials(patient) {
    const f = patient.first_name?.[0] || '';
    const l = patient.last_name?.[0] || '';
    return (f + l).toUpperCase() || '?';
}

function avatarClasses(patient) {
    const status = patient.alert_status || patient.status;
    if (status === 'alert') return 'bg-red-100 text-red-700';
    if (status === 'review') return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
}

onMounted(() => {
    doctorStore.fetchPatients();
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
