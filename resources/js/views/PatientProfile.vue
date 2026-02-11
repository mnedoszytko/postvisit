<template>
  <PatientLayout>
    <div class="space-y-6">
      <!-- Profile header -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 flex items-center gap-6">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center text-2xl font-bold text-emerald-700">
          {{ initials }}
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ auth.user?.name || 'Patient' }}</h1>
          <p class="text-gray-500">{{ auth.user?.email }}</p>
        </div>
      </div>

      <!-- Visit history -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Visit History</h2>
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
          <div v-if="visitStore.loading" class="p-6 text-center text-gray-400">
            Loading visits...
          </div>
          <div v-else-if="visitStore.visits.length === 0" class="p-6 text-center text-gray-400">
            No visits yet. Record your first visit with Companion Scribe.
          </div>
          <router-link
            v-for="visit in visitStore.visits"
            :key="visit.id"
            :to="`/visits/${visit.id}`"
            class="block p-4 hover:bg-gray-50 transition-colors"
          >
            <p class="font-medium text-gray-900">{{ visit.reason_for_visit || visit.visit_type || 'Visit' }}</p>
            <p class="text-sm text-gray-500">
              {{ visit.started_at ? new Date(visit.started_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '' }}
              <span v-if="visit.practitioner" class="ml-2">
                &middot; Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
                <span v-if="visit.practitioner.primary_specialty" class="text-gray-400">, {{ visit.practitioner.primary_specialty }}</span>
              </span>
            </p>
          </router-link>
        </div>
      </section>

      <!-- Quick actions -->
      <section class="space-y-3">
        <router-link
          to="/health"
          class="block w-full text-center py-3 bg-white text-emerald-700 border border-emerald-200 rounded-xl font-medium hover:bg-emerald-50 transition-colors"
        >
          View Health Dashboard
        </router-link>
        <router-link
          to="/scribe"
          class="block w-full text-center py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
        >
          Record New Visit
        </router-link>
      </section>
    </div>
  </PatientLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useVisitStore } from '@/stores/visit';
import PatientLayout from '@/layouts/PatientLayout.vue';

const auth = useAuthStore();
const visitStore = useVisitStore();

const initials = computed(() => {
    const name = auth.user?.name || '';
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
});

onMounted(() => {
    const patientId = auth.user?.patient_id || auth.user?.patient?.id;
    if (patientId) {
        visitStore.fetchVisits(patientId);
    }
});
</script>
