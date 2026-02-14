<template>
  <PatientLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">My Health</h1>
        <router-link to="/profile" class="text-sm text-emerald-600 hover:text-emerald-700">&larr; Back to Profile</router-link>
      </div>

      <HealthTabNav v-model="activeTab" />

      <div v-if="loading" class="text-center py-12 text-gray-400">Loading health data...</div>

      <template v-else>
        <HealthProfileTab
          v-if="activeTab === 'profile'"
          :patient="patient"
          :conditions="conditions"
          :visits="visitStore.visits.slice(0, 3)"
          @patient-updated="onPatientUpdated"
          @navigate-tab="onNavigateTab"
        />
        <VitalsTab
          v-if="activeTab === 'vitals'"
          :observations="observations"
          :device-data="deviceData"
          :scroll-to="vitalsScrollTo"
        />
        <LabResultsTab
          v-if="activeTab === 'labs'"
          :observations="observations"
          :documents="labDocuments"
        />
        <ConnectedServicesTab v-if="activeTab === 'services'" />
        <DocumentsTab
          v-if="activeTab === 'documents'"
          :documents="documents"
          :patient-id="patientId"
          @documents-changed="onDocumentsChanged"
        />
      </template>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useApi } from '@/composables/useApi';
import { useVisitStore } from '@/stores/visit';
import PatientLayout from '@/layouts/PatientLayout.vue';
import HealthTabNav from '@/components/health/HealthTabNav.vue';
import HealthProfileTab from '@/components/health/HealthProfileTab.vue';
import ConnectedServicesTab from '@/components/health/ConnectedServicesTab.vue';
import VitalsTab from '@/components/health/VitalsTab.vue';
import LabResultsTab from '@/components/health/LabResultsTab.vue';
import DocumentsTab from '@/components/health/DocumentsTab.vue';
const auth = useAuthStore();
const api = useApi();
const visitStore = useVisitStore();
const setChatContext = inject('setChatContext', () => {});

const tabContextMap = {
    'profile': 'health',
    'vitals': 'vitals',
    'labs': 'lab',
    'services': 'apple watch',
    'documents': 'health',
};

const activeTab = ref('profile');
const vitalsScrollTo = ref('');

function onNavigateTab(tab, scrollTarget = '') {
    activeTab.value = tab;
    vitalsScrollTo.value = scrollTarget;
}

// Update chat context when switching between health sub-tabs
watch(activeTab, (tab) => {
    const ctx = tabContextMap[tab] || 'health';
    setChatContext(ctx);
});
const loading = ref(true);
const patient = ref(null);
const observations = ref([]);
const conditions = ref([]);
const deviceData = ref(null);
const documents = ref([]);

const patientId = computed(() => auth.user?.patient_id || auth.user?.patient?.id || null);
const labDocuments = computed(() => documents.value.filter(d => d.document_type === 'lab_result'));

function onPatientUpdated(updatedPatient) {
    patient.value = updatedPatient;
}

function onDocumentsChanged(updatedDocs) {
    documents.value = updatedDocs;
}

onMounted(async () => {
    if (!patientId.value) {
        loading.value = false;
        return;
    }

    try {
        const [patientRes, obsRes, condRes, deviceRes, docsRes] = await Promise.allSettled([
            api.get(`/patients/${patientId.value}`),
            api.get(`/patients/${patientId.value}/observations`),
            api.get(`/patients/${patientId.value}/conditions`),
            fetch('/data/apple-watch-alex.json').then(r => r.ok ? r.json() : null),
            api.get(`/patients/${patientId.value}/documents`),
        ]);

        if (patientRes.status === 'fulfilled') {
            patient.value = patientRes.value.data.data || null;
        }
        if (obsRes.status === 'fulfilled') {
            observations.value = obsRes.value.data.data || [];
        }
        if (condRes.status === 'fulfilled') {
            conditions.value = condRes.value.data.data || [];
        }
        if (deviceRes.status === 'fulfilled' && deviceRes.value) {
            deviceData.value = deviceRes.value;
        }
        if (docsRes.status === 'fulfilled') {
            documents.value = docsRes.value.data.data || [];
        }

        if (!visitStore.visits.length) {
            visitStore.fetchVisits(patientId.value);
        }
    } finally {
        loading.value = false;
    }
});
</script>
