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
          <template v-else>
            <router-link
              v-for="visit in displayedVisits"
              :key="visit.id"
              :to="`/visits/${visit.id}`"
              class="block p-4 hover:bg-gray-50 transition-colors"
            >
              <p class="font-medium text-gray-900">{{ shortTitle(visit) }}</p>
              <div class="flex flex-wrap items-center gap-2 mt-1.5">
                <span v-if="visit.started_at" class="inline-flex items-center gap-1.5 text-sm text-gray-500">
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                  {{ formatDate(visit.started_at) }}
                </span>
                <span v-if="visit.visit_type" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                  {{ formatVisitType(visit.visit_type) }}
                </span>
                <span v-if="visit.practitioner" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
                  <span v-if="visit.practitioner.primary_specialty" class="text-blue-500">&middot; {{ visit.practitioner.primary_specialty }}</span>
                </span>
              </div>
            </router-link>
            <button
              v-if="visitStore.visits.length > visibleCount && !showAll"
              class="w-full p-3 text-sm font-medium text-emerald-600 hover:bg-gray-50 transition-colors"
              @click="showAll = true"
            >
              Show {{ visitStore.visits.length - visibleCount }} more visit{{ visitStore.visits.length - visibleCount > 1 ? 's' : '' }}
            </button>
          </template>
        </div>
      </section>

      <!-- Record New Visit (primary action) -->
      <router-link
        to="/scribe"
        class="block w-full text-center py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
      >
        Record New Visit
      </router-link>

      <!-- Quick links -->
      <section class="space-y-3">
        <router-link
          to="/health"
          class="block w-full text-center py-3 bg-white text-emerald-700 border border-emerald-200 rounded-xl font-medium hover:bg-emerald-50 transition-colors"
        >
          Health Dashboard
        </router-link>
        <router-link
          to="/library"
          class="block w-full text-center py-3 bg-white text-emerald-700 border border-emerald-200 rounded-xl font-medium hover:bg-emerald-50 transition-colors"
        >
          Medical Library
        </router-link>
      </section>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useVisitStore } from '@/stores/visit';
import PatientLayout from '@/layouts/PatientLayout.vue';


const auth = useAuthStore();
const visitStore = useVisitStore();

const visibleCount = 5;
const showAll = ref(false);

const displayedVisits = computed(() => {
    if (showAll.value) return visitStore.visits;
    return visitStore.visits.slice(0, visibleCount);
});

const initials = computed(() => {
    const name = auth.user?.name || '';
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatVisitType(type) {
    if (!type) return '';
    return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function shortTitle(visit) {
    const raw = visit.reason_for_visit || visit.visit_type || 'Visit';
    if (raw.length <= 60) return raw;
    return raw.slice(0, 57) + '...';
}

onMounted(() => {
    const patientId = auth.user?.patient_id || auth.user?.patient?.id;
    if (patientId) {
        visitStore.fetchVisits(patientId);
    }
});
</script>
