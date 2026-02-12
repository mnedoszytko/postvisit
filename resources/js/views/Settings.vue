<template>
  <component :is="layout">
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-gray-900">Settings</h1>

      <!-- AI Settings -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">AI Settings</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
          <AiTierSelector />
        </div>
      </section>
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import PatientLayout from '@/layouts/PatientLayout.vue';
import DoctorLayout from '@/layouts/DoctorLayout.vue';
import AiTierSelector from '@/components/AiTierSelector.vue';

const auth = useAuthStore();
const layout = computed(() => auth.isDoctor ? DoctorLayout : PatientLayout);
</script>
