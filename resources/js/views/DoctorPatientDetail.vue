<template>
  <DoctorLayout>
    <div class="space-y-6">
      <router-link to="/doctor" class="text-sm text-emerald-600 hover:text-emerald-700">
        Back to Dashboard
      </router-link>

      <!-- Patient profile -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 flex items-center gap-6">
        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center text-xl font-bold text-emerald-700">
          {{ patient?.first_name?.[0] || '?' }}
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ patient ? `${patient.first_name} ${patient.last_name}` : 'Patient' }}</h1>
          <p class="text-gray-500">
            <template v-if="patientAge">Age {{ patientAge }}</template>
            <template v-if="patientAge && activeConditions.length"> &middot; </template>
            {{ activeConditions.join(', ') }}
          </p>
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
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-900">{{ formatVisitType(visit.visit_type) }}</p>
              <p class="text-sm text-gray-500">{{ formatDate(visit.started_at) }}</p>
            </div>
            <p v-if="visit.practitioner" class="text-sm text-gray-500">
              Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
            </p>
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
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
import DoctorLayout from '@/layouts/DoctorLayout.vue';

const route = useRoute();
const api = useApi();
const patient = ref(null);
const visits = ref([]);

const patientAge = computed(() => {
    if (!patient.value?.dob) return null;
    const dob = new Date(patient.value.dob);
    const now = new Date();
    let age = now.getFullYear() - dob.getFullYear();
    const monthDiff = now.getMonth() - dob.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < dob.getDate())) age--;
    return age;
});

const activeConditions = computed(() => {
    if (!patient.value?.conditions?.length) return [];
    return patient.value.conditions.map(c => c.code_display);
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatVisitType(type) {
    if (!type) return 'Visit';
    return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

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
