<template>
  <DoctorLayout>
    <div class="space-y-8">
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>

      <!-- Alert Panel -->
      <section v-if="doctorStore.alerts.length > 0 || doctorStore.alertsLoading">
        <h2 class="text-lg font-semibold text-red-700 mb-3 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          Requires Your Attention
        </h2>

        <div v-if="doctorStore.alertsLoading" class="bg-red-50 rounded-2xl border border-red-200 p-6 text-center text-gray-400">
          Loading alerts...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="(alert, idx) in doctorStore.alerts"
            :key="idx"
            :class="[
              'rounded-2xl border p-5',
              alert.severity === 'high'
                ? 'bg-red-50 border-red-300'
                : 'bg-amber-50 border-amber-300'
            ]"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                      alert.severity === 'high'
                        ? 'bg-red-100 text-red-700'
                        : 'bg-amber-100 text-amber-700'
                    ]"
                  >
                    {{ alert.type === 'weight_gain' ? 'Weight Alert' : 'BP Trend' }}
                  </span>
                </div>
                <p class="font-semibold text-gray-900">{{ alert.patient_name }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ alert.message }}</p>

                <div v-if="alert.type === 'weight_gain'" class="mt-2 text-xs text-gray-500">
                  {{ alert.data.from }} kg &rarr; {{ alert.data.to }} kg
                  <span class="text-red-600 font-medium">(+{{ alert.data.delta_kg }} kg)</span>
                </div>

                <div v-if="alert.type === 'elevated_bp' && alert.data.readings" class="mt-2 flex gap-2 flex-wrap">
                  <span
                    v-for="(r, i) in alert.data.readings"
                    :key="i"
                    class="text-xs bg-white/70 border border-amber-200 rounded px-2 py-0.5"
                  >
                    {{ r.systolic }}/{{ r.diastolic }} <span class="text-gray-400">{{ r.date }}</span>
                  </span>
                </div>
              </div>

              <router-link
                :to="`/doctor/patients/${alert.patient_id}`"
                class="shrink-0 inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors"
              >
                View
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <div v-else-if="!doctorStore.alertsLoading" class="bg-emerald-50 rounded-2xl border border-emerald-200 p-4 text-sm text-emerald-700">
        No alerts at this time.
      </div>

      <!-- Stats overview -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500">Patients</p>
          <p class="text-3xl font-bold text-gray-900">{{ doctorStore.dashboard?.stats?.total_patients || 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500">Unread Messages</p>
          <p class="text-3xl font-bold text-emerald-600">{{ doctorStore.dashboard?.stats?.unread_notifications || 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500">Total Visits</p>
          <p class="text-3xl font-bold text-emerald-700">{{ doctorStore.dashboard?.stats?.total_visits || 0 }}</p>
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

        <div v-if="doctorStore.loading" class="bg-white rounded-2xl border border-gray-200 p-6 text-center text-gray-400">
          Loading patients...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <router-link
            v-for="patient in doctorStore.patients"
            :key="patient.id"
            :to="`/doctor/patients/${patient.id}`"
            class="bg-white rounded-2xl border border-gray-200 p-5 hover:shadow-md hover:border-emerald-300 transition-all block"
          >
            <div class="flex items-start gap-4">
              <div class="w-11 h-11 bg-emerald-100 rounded-full flex items-center justify-center text-sm font-bold text-emerald-700 shrink-0">
                {{ patient.first_name?.[0] || '?' }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 truncate mb-1">{{ patient.first_name }} {{ patient.last_name }}</p>

                <p v-if="patient.primary_condition" class="text-sm text-gray-600 truncate">
                  {{ patient.primary_condition }}
                </p>

                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                  <span>{{ patient.visits_count || 0 }} visit{{ patient.visits_count !== 1 ? 's' : '' }}</span>
                  <span v-if="patient.last_visit_date">Last: {{ formatDate(patient.last_visit_date) }}</span>
                </div>
              </div>
            </div>
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

const doctorStore = useDoctorStore();
const search = ref('');

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

onMounted(() => {
    doctorStore.fetchDashboard();
    doctorStore.fetchPatients();
    doctorStore.fetchAlerts();
});
</script>
