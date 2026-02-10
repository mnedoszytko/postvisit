<template>
  <PatientLayout>
    <div class="relative">
      <!-- Visit header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Visit Summary</h1>
        <p v-if="visitStore.currentVisit" class="text-gray-500">
          {{ visitStore.currentVisit.visit_date }} — {{ visitStore.currentVisit.visit_type }}
        </p>
      </div>

      <!-- Loading state -->
      <div v-if="visitStore.loading" class="text-center py-12 text-gray-400">
        Loading visit data...
      </div>

      <!-- Visit sections -->
      <div v-else class="space-y-4">
        <VisitSection
          v-for="section in sections"
          :key="section.key"
          :title="section.title"
          :content="section.content"
          @explain="openChat(section.title)"
        />
      </div>

      <!-- Chat Panel -->
      <ChatPanel
        v-if="chatOpen"
        :visit-id="route.params.id"
        :initial-context="chatContext"
        @close="chatOpen = false"
      />

      <!-- Floating chat button -->
      <button
        v-if="!chatOpen"
        class="fixed bottom-6 right-6 w-14 h-14 bg-emerald-600 text-white rounded-full shadow-lg hover:bg-emerald-700 transition-colors flex items-center justify-center text-xl"
        @click="openChat()"
      >
        ?
      </button>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useVisitStore } from '@/stores/visit';
import PatientLayout from '@/layouts/PatientLayout.vue';
import VisitSection from '@/components/VisitSection.vue';
import ChatPanel from '@/components/ChatPanel.vue';

const route = useRoute();
const visitStore = useVisitStore();
const chatOpen = ref(false);
const chatContext = ref('');

const sections = computed(() => {
    const visit = visitStore.currentVisit;
    if (!visit) return [];
    return [
        { key: 'reason', title: 'Reason for Visit', content: visit.reason_for_visit },
        { key: 'symptoms', title: 'Symptoms', content: visit.symptoms },
        { key: 'history', title: 'History / Interview', content: visit.history },
        { key: 'comorbidities', title: 'Comorbidities', content: visit.comorbidities },
        { key: 'medications', title: 'Current Medications', content: visit.current_medications },
        { key: 'exam', title: 'Physical Examination', content: visit.physical_exam },
        { key: 'tests', title: 'Additional Tests', content: visit.additional_tests },
        { key: 'conclusions', title: 'Conclusions', content: visit.conclusions },
        { key: 'recommendations', title: 'Recommendations', content: visit.recommendations },
        { key: 'next_steps', title: 'Next Steps', content: visit.next_steps },
    ].filter(s => s.content);
});

function openChat(context = '') {
    chatContext.value = context;
    chatOpen.value = true;
}

onMounted(() => {
    visitStore.fetchVisit(route.params.id);
});
</script>
