<template>
  <PatientLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Medications</h1>
        <router-link
          :to="`/visits/${route.params.id}`"
          class="text-sm text-emerald-600 hover:text-emerald-700"
        >
          Back to Visit
        </router-link>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="text-center py-12 text-gray-400">
        Loading medications...
      </div>

      <!-- Medication cards -->
      <div v-else class="space-y-4">
        <MedCard
          v-for="med in medications"
          :key="med.id"
          :medication="med"
          @explain="openChat(med.name)"
        />
        <div v-if="medications.length === 0" class="text-center py-12 text-gray-400">
          No medications found for this visit.
        </div>
      </div>

      <!-- Chat Panel -->
      <ChatPanel
        v-if="chatOpen"
        :visit-id="route.params.id"
        :initial-context="chatContext"
        @close="chatOpen = false"
      />
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
import PatientLayout from '@/layouts/PatientLayout.vue';
import MedCard from '@/components/MedCard.vue';
import ChatPanel from '@/components/ChatPanel.vue';

const route = useRoute();
const api = useApi();
const loading = ref(true);
const medications = ref([]);
const chatOpen = ref(false);
const chatContext = ref('');

function openChat(context) {
    chatContext.value = `Tell me about ${context}`;
    chatOpen.value = true;
}

onMounted(async () => {
    try {
        const { data } = await api.get(`/visits/${route.params.id}/prescriptions`);
        medications.value = data.data;
    } catch {
        // Handled by API interceptor
    } finally {
        loading.value = false;
    }
});
</script>
