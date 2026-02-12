<template>
  <PatientLayout>
    <div class="space-y-6">
      <!-- Profile header -->
      <router-link to="/health" class="block bg-white rounded-2xl border border-gray-200 p-6 flex items-center gap-6 hover:border-emerald-300 hover:shadow-md transition-all duration-200 group cursor-pointer">
        <img
          v-if="auth.user?.photo_url"
          :src="auth.user.photo_url"
          :alt="auth.user.name"
          class="w-20 h-20 rounded-full object-cover"
        />
        <div v-else class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center text-2xl font-bold text-emerald-700">
          {{ initials }}
        </div>
        <div class="min-w-0 flex-1">
          <h1 class="text-2xl font-bold text-gray-900 truncate">{{ auth.user?.name || 'Patient' }}</h1>
          <p v-if="ageGenderLine" class="text-sm text-gray-600">{{ ageGenderLine }}</p>
          <p class="text-gray-500 truncate">{{ auth.user?.email }}</p>
        </div>
        <svg class="w-5 h-5 ml-auto text-gray-300 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
      </router-link>

      <!-- Visit history -->
      <section>
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold text-gray-800">Visit History</h2>
          <AskAiButton v-if="visitStore.visits.length > 0" @ask="openGlobalChat('visits')" />
        </div>
        <div class="space-y-3">
          <div v-if="visitStore.loading" class="bg-white rounded-2xl border border-gray-200 p-6 text-center text-gray-400">
            Loading visits...
          </div>
          <div v-else-if="visitStore.visits.length === 0" class="bg-white rounded-2xl border border-gray-200 p-6 text-center text-gray-400">
            No visits yet. Record your first visit with Companion Scribe.
          </div>
          <template v-else>
            <router-link
              v-for="visit in displayedVisits"
              :key="visit.id"
              :to="`/visits/${visit.id}`"
              class="flex items-center gap-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 p-5 hover:bg-indigo-50 hover:border-indigo-200 hover:shadow-md transition-all duration-200 group"
            >
              <VisitDateBadge :date="visit.started_at" />

              <div class="flex-1 min-w-0">
                <!-- Visit type badge -->
                <div class="flex items-center justify-between mb-2">
                  <span v-if="visit.visit_type" class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                    {{ formatVisitType(visit.visit_type) }}
                  </span>
                </div>

                <!-- Doctor name + specialty -->
                <div v-if="visit.practitioner" class="flex items-center gap-2.5 mb-1">
                  <img
                    v-if="visit.practitioner.photo_url"
                    :src="visit.practitioner.photo_url"
                    :alt="`Dr. ${visit.practitioner.first_name} ${visit.practitioner.last_name}`"
                    class="w-9 h-9 rounded-full object-cover shrink-0"
                  />
                  <div v-else class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                    <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  </div>
                  <div>
                    <p class="text-base font-bold text-gray-900 leading-tight">
                      Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
                    </p>
                    <p v-if="visit.practitioner.primary_specialty" class="text-sm text-blue-600 capitalize">
                      {{ visit.practitioner.primary_specialty }}
                    </p>
                  </div>
                </div>

                <!-- Visit reason -->
                <p v-if="visit.reason_for_visit" class="text-sm text-gray-500 leading-snug line-clamp-1">
                  {{ visit.reason_for_visit }}
                </p>
              </div>

              <svg class="w-5 h-5 text-indigo-300 group-hover:text-indigo-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </router-link>
            <button
              v-if="visitStore.visits.length > visibleCount && !showAll"
              class="w-full p-3 text-sm font-medium text-emerald-600 bg-white rounded-2xl border border-gray-200 hover:bg-gray-50 transition-colors"
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
        class="flex items-center justify-center gap-2 w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
      >
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-300 opacity-60"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-red-400"></span>
        </span>
        Record New Visit
      </router-link>

      <!-- Quick links — side by side -->
      <section class="grid grid-cols-2 gap-3">
        <div class="relative bg-white border border-emerald-200 rounded-2xl hover:bg-emerald-50 hover:border-emerald-300 hover:shadow-md transition-all group">
          <div class="absolute top-2 right-2 z-10" @click.prevent.stop>
            <AskAiButton @ask="openGlobalChat('health record')" />
          </div>
          <router-link
            to="/health"
            class="flex flex-col items-center gap-2 py-5"
          >
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
              <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
              </svg>
            </div>
            <span class="text-sm font-semibold text-emerald-700">Health Record</span>
            <span v-if="healthRecordCount > 0" class="text-xs bg-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full font-medium">
              {{ healthRecordCount }} {{ healthRecordCount === 1 ? 'entry' : 'entries' }}
            </span>
          </router-link>
        </div>
        <div class="relative bg-white border border-indigo-200 rounded-2xl hover:bg-indigo-50 hover:border-indigo-300 hover:shadow-md transition-all group">
          <div class="absolute top-2 right-2 z-10" @click.prevent.stop>
            <AskAiButton @ask="openGlobalChat('reference')" />
          </div>
          <router-link
            to="/library"
            class="flex flex-col items-center gap-2 py-5"
          >
            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
              <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
              </svg>
            </div>
            <span class="text-sm font-semibold text-indigo-700">Reference</span>
            <span v-if="libraryCount > 0" class="text-xs bg-indigo-100 text-indigo-700 px-2.5 py-0.5 rounded-full font-medium">
              {{ libraryCount }} {{ libraryCount === 1 ? 'reference' : 'references' }}
            </span>
          </router-link>
        </div>
      </section>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useVisitStore } from '@/stores/visit';
import { useApi } from '@/composables/useApi';
import PatientLayout from '@/layouts/PatientLayout.vue';
import VisitDateBadge from '@/components/VisitDateBadge.vue';
import AskAiButton from '@/components/AskAiButton.vue';

const openGlobalChat = inject('openGlobalChat', () => {});

const auth = useAuthStore();
const visitStore = useVisitStore();
const api = useApi();

const healthRecordCount = ref(0);
const libraryCount = ref(0);

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

const patientAge = computed(() => {
    const dob = auth.user?.patient?.dob;
    if (!dob) return null;
    const birth = new Date(dob);
    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    const monthDiff = now.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
        age--;
    }
    return age;
});

const patientGender = computed(() => {
    const g = auth.user?.patient?.gender;
    if (!g) return null;
    return g.charAt(0).toUpperCase() + g.slice(1);
});

const ageGenderLine = computed(() => {
    const parts = [];
    if (patientGender.value) parts.push(patientGender.value);
    if (patientAge.value !== null) parts.push(`${patientAge.value} y.o.`);
    return parts.join(', ');
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

onMounted(async () => {
    const patientId = auth.user?.patient_id || auth.user?.patient?.id;
    if (patientId) {
        visitStore.fetchVisits(patientId);

        // Fetch counts for quick links
        const [condRes, rxRes, libRes] = await Promise.allSettled([
            api.get(`/patients/${patientId}/conditions`),
            api.get(`/patients/${patientId}/prescriptions`),
            api.get('/library'),
        ]);

        const condCount = condRes.status === 'fulfilled' ? (condRes.value.data.data || []).length : 0;
        const rxCount = rxRes.status === 'fulfilled' ? (rxRes.value.data.data || []).length : 0;
        healthRecordCount.value = condCount + rxCount;

        // Reference Library shows conditions + medications + user uploads
        const uploadCount = libRes.status === 'fulfilled'
            ? (libRes.value.data.data?.total ?? (libRes.value.data.data?.data || libRes.value.data.data || []).length)
            : 0;
        libraryCount.value = condCount + rxCount + uploadCount;
    }
});
</script>
