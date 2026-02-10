<template>
  <DoctorLayout>
    <div class="space-y-8">
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>

      <!-- Stats overview -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500">Patients</p>
          <p class="text-3xl font-bold text-gray-900">{{ doctorStore.dashboard?.patient_count || 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500">Unread Messages</p>
          <p class="text-3xl font-bold text-emerald-600">{{ doctorStore.dashboard?.unread_messages || 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500">Alerts</p>
          <p class="text-3xl font-bold text-red-600">{{ doctorStore.dashboard?.alert_count || 0 }}</p>
        </div>
      </div>

      <!-- Patient list -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-800">Patients</h2>
          <input
            v-model="search"
            type="text"
            placeholder="Search patients..."
            class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            @input="handleSearch"
          />
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
          <div v-if="doctorStore.loading" class="p-6 text-center text-gray-400">
            Loading patients...
          </div>
          <router-link
            v-for="patient in doctorStore.patients"
            :key="patient.id"
            :to="`/doctor/patients/${patient.id}`"
            class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-sm font-bold text-emerald-700">
              {{ patient.name?.[0] || '?' }}
            </div>
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ patient.name }}</p>
              <p class="text-sm text-gray-500">Last visit: {{ patient.last_visit_date || 'N/A' }}</p>
            </div>
            <NotificationBanner v-if="patient.has_unread" text="New" />
          </router-link>
        </div>
      </section>
    </div>
  </DoctorLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useDoctorStore } from '@/stores/doctor';
import DoctorLayout from '@/layouts/DoctorLayout.vue';
import NotificationBanner from '@/components/NotificationBanner.vue';

const doctorStore = useDoctorStore();
const search = ref('');

let debounceTimer = null;
function handleSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        doctorStore.fetchPatients(search.value);
    }, 300);
}

onMounted(() => {
    doctorStore.fetchDashboard();
    doctorStore.fetchPatients();
});
</script>
