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
          @navigate-tab="activeTab = $event"
        />
        <VitalsTab
          v-if="activeTab === 'vitals'"
          :observations="observations"
          :device-data="deviceData"
        />
        <LabResultsTab
          v-if="activeTab === 'labs'"
          :observations="observations"
        />
        <ConnectedServicesTab v-if="activeTab === 'services'" />
        <DocumentsTab v-if="activeTab === 'documents'" />
      </template>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
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

const activeTab = ref('profile');
const loading = ref(true);
const patient = ref(null);
const observations = ref([]);
const conditions = ref([]);
const deviceData = ref(null);

function onPatientUpdated(updatedPatient) {
    patient.value = updatedPatient;
}

onMounted(async () => {
    const patientId = auth.user?.patient_id || auth.user?.patient?.id;
    if (!patientId) {
        loading.value = false;
        return;
    }

    try {
        const [patientRes, obsRes, condRes, deviceRes] = await Promise.allSettled([
            api.get(`/patients/${patientId}`),
            api.get(`/patients/${patientId}/observations`),
            api.get(`/patients/${patientId}/conditions`),
            fetch('/data/apple-watch-alex.json').then(r => r.ok ? r.json() : null),
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

        if (!visitStore.visits.length) {
            visitStore.fetchVisits(patientId);
        }
    } finally {
        loading.value = false;
    }
});
</script>
