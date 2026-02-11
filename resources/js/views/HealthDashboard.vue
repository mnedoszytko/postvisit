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
          @patient-updated="onPatientUpdated"
        />
        <ConnectedServicesTab v-if="activeTab === 'services'" />
        <VitalsLabsTab
          v-if="activeTab === 'vitals'"
          :observations="observations"
          :device-data="deviceData"
        />
        <DocumentsTab v-if="activeTab === 'documents'" />
      </template>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useApi } from '@/composables/useApi';
import PatientLayout from '@/layouts/PatientLayout.vue';
import HealthTabNav from '@/components/health/HealthTabNav.vue';
import HealthProfileTab from '@/components/health/HealthProfileTab.vue';
import ConnectedServicesTab from '@/components/health/ConnectedServicesTab.vue';
import VitalsLabsTab from '@/components/health/VitalsLabsTab.vue';
import DocumentsTab from '@/components/health/DocumentsTab.vue';

const auth = useAuthStore();
const api = useApi();

const activeTab = ref('profile');
const loading = ref(true);
const patient = ref(null);
const observations = ref([]);
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
        const [patientRes, obsRes, deviceRes] = await Promise.allSettled([
            api.get(`/patients/${patientId}`),
            api.get(`/patients/${patientId}/observations`),
            fetch('/demo/apple-watch-alex.json').then(r => r.ok ? r.json() : null),
        ]);

        if (patientRes.status === 'fulfilled') {
            patient.value = patientRes.value.data.data || null;
        }
        if (obsRes.status === 'fulfilled') {
            observations.value = obsRes.value.data.data || [];
        }
        if (deviceRes.status === 'fulfilled' && deviceRes.value) {
            deviceData.value = deviceRes.value;
        }
    } finally {
        loading.value = false;
    }
});
</script>
