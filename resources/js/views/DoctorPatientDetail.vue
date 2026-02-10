<template>
  <DoctorLayout>
    <div class="space-y-6">
      <router-link to="/doctor" class="text-sm text-emerald-600 hover:text-emerald-700">
        Back to Dashboard
      </router-link>

      <!-- Patient profile -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 flex items-center gap-6">
        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center text-xl font-bold text-emerald-700">
          {{ patient?.name?.[0] || '?' }}
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ patient?.name || 'Patient' }}</h1>
          <p class="text-gray-500">{{ patient?.age ? `Age ${patient.age}` : '' }} {{ patient?.conditions?.join(', ') || '' }}</p>
        </div>
      </div>

      <!-- Visit history -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Visit History</h2>
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
          <div v-if="visits.length === 0" class="p-6 text-center text-gray-400">
            No visits recorded.
          </div>
          <div v-for="visit in visits" :key="visit.id" class="p-4">
            <p class="font-medium text-gray-900">{{ visit.visit_type || 'Visit' }}</p>
            <p class="text-sm text-gray-500">{{ visit.visit_date }}</p>
          </div>
        </div>
      </section>

      <!-- AI Audit Trail -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">AI Audit Trail</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-gray-400 text-center">Patient's AI interactions will appear here.</p>
        </div>
      </section>

      <!-- Messages -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Messages</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-gray-400 text-center">Patient messages will appear here.</p>
        </div>
      </section>
    </div>
  </DoctorLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
import DoctorLayout from '@/layouts/DoctorLayout.vue';

const route = useRoute();
const api = useApi();
const patient = ref(null);
const visits = ref([]);

onMounted(async () => {
    try {
        const [patientRes, visitsRes] = await Promise.all([
            api.get(`/doctor/patients/${route.params.id}`),
            api.get(`/doctor/patients/${route.params.id}/visits`),
        ]);
        patient.value = patientRes.data.data;
        visits.value = visitsRes.data.data;
    } catch {
        // Handled by API interceptor
    }
});
</script>
