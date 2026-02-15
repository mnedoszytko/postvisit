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
                    {{ alertLabel(alert.type) }}
                  </span>
                </div>
                <p class="font-semibold text-gray-900">{{ alert.patient_name }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ alert.message }}</p>

                <div v-if="alert.type === 'weight_gain'" class="mt-2 text-xs text-gray-500">
                  {{ alert.data.from }} kg &rarr; {{ alert.data.to }} kg
                  <span class="text-red-600 font-medium">(+{{ alert.data.delta_kg }} kg)</span>
                </div>

                <div v-if="alert.type === 'hr_drop'" class="mt-2 flex items-center gap-3 text-xs text-gray-600">
                  <span class="font-mono">{{ alert.data.prior_avg_bpm }} bpm</span>
                  <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17l5-5-5-5M6 17l5-5-5-5" /></svg>
                  <span class="font-mono font-bold text-red-600">{{ alert.data.current_bpm }} bpm</span>
                  <span class="text-red-500 font-medium">(&darr;{{ alert.data.drop_bpm }} bpm)</span>
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

              <button
                class="shrink-0 inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors"
                @click="scrollToPatient(alert.patient_id)"
              >
                View
              </button>
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
        <router-link
          to="/doctor/patients"
          class="bg-white rounded-2xl border border-gray-200 p-6 hover:border-emerald-300 hover:shadow-sm transition-all block"
        >
          <p class="text-sm text-gray-500">Unread Messages</p>
          <p class="text-3xl font-bold text-emerald-600">{{ doctorStore.dashboard?.stats?.unread_notifications || 0 }}</p>
          <p class="text-xs text-emerald-500 mt-1">View patients &rarr;</p>
        </router-link>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500">Total Visits</p>
          <p class="text-3xl font-bold text-emerald-700">{{ doctorStore.dashboard?.stats?.total_visits || 0 }}</p>
        </div>
      </div>

      <!-- Recent Patients -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-800">Recent Patients</h2>
          <router-link
            to="/doctor/patients"
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors"
          >
            View all &rarr;
          </router-link>
        </div>

        <div v-if="doctorStore.loading" class="bg-white rounded-2xl border border-gray-200 p-6 text-center text-gray-400">
          Loading patients...
        </div>

        <div v-else class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-50">
          <router-link
            v-for="patient in recentPatients"
            :key="patient.id"
            :id="`patient-${patient.id}`"
            :to="`/doctor/patients/${patient.id}`"
            class="flex items-center gap-4 p-4 hover:bg-gray-50/50 transition-colors"
          >
            <div class="relative shrink-0">
              <img
                v-if="patient.photo_url"
                :src="patient.photo_url"
                :alt="`${patient.first_name} ${patient.last_name}`"
                class="w-10 h-10 rounded-full object-cover"
              />
              <div
                v-else
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold',
                  avatarClasses(patient)
                ]"
              >
                {{ initials(patient) }}
              </div>
              <span
                v-if="patient.unread_count > 0"
                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white"
              >
                {{ patient.unread_count > 9 ? '9+' : patient.unread_count }}
              </span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-900 truncate">
                {{ patient.first_name }} {{ patient.last_name }}<span v-if="patient.age" class="text-gray-400 font-normal">, {{ patient.age }}</span>
              </p>
              <p v-if="patient.primary_condition" class="text-sm text-gray-500 truncate">{{ patient.primary_condition }}</p>
            </div>
            <div v-if="patient.last_vitals" class="hidden sm:flex items-center gap-3 text-xs text-gray-500">
              <span v-if="patient.last_vitals.bp" class="inline-flex items-center gap-1">
                <svg class="w-3 h-3 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg>
                {{ patient.last_vitals.bp }}
              </span>
              <span v-if="patient.last_vitals.weight" class="inline-flex items-center gap-1">
                <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                {{ formatWeight(patient.last_vitals.weight) }}
              </span>
            </div>
            <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
          </router-link>
          <div v-if="recentPatients.length === 0 && !doctorStore.loading" class="p-6 text-center text-gray-400">
            No patients found.
          </div>
        </div>
      </section>
    </div>
  </DoctorLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useDoctorStore } from '@/stores/doctor';
import DoctorLayout from '@/layouts/DoctorLayout.vue';

const doctorStore = useDoctorStore();

const recentPatients = computed(() => doctorStore.patients.slice(0, 3));

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

function alertLabel(type) {
    if (type === 'weight_gain') return 'Weight Alert';
    if (type === 'hr_drop') return 'Heart Rate';
    return 'BP Trend';
}

function scrollToPatient(patientId) {
    const el = document.getElementById(`patient-${patientId}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('ring-2', 'ring-red-400', 'bg-red-50/40');
        setTimeout(() => el.classList.remove('ring-2', 'ring-red-400', 'bg-red-50/40'), 2000);
    }
}

function avatarClasses(patient) {
    const status = patient.alert_status || patient.status;
    if (status === 'alert') return 'bg-red-100 text-red-700';
    if (status === 'review') return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
}

onMounted(() => {
    doctorStore.fetchDashboard();
    doctorStore.fetchPatients();
    doctorStore.fetchAlerts();
});
</script>
