<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-50 to-white">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur border-b border-gray-200 sticky top-0 z-40">
      <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <router-link to="/" class="text-xl font-semibold text-emerald-700">
          PostVisit.ai
        </router-link>
        <span class="text-xs font-medium bg-amber-100 text-amber-700 px-3 py-1 rounded-full">
          Demo Mode
        </span>
      </div>
    </header>

    <!-- Welcome -->
    <div v-if="step === 'welcome'" class="max-w-lg mx-auto px-4 py-16 text-center space-y-8">
      <h1 class="text-3xl font-bold text-gray-900">Experience PostVisit.ai</h1>
      <p class="text-gray-600">
        See how PostVisit.ai works with a simulated cardiology visit.
      </p>

      <div class="space-y-4">
        <button
          class="w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
          @click="startDemo('voice')"
        >
          Try Voice Recording
        </button>
        <button
          class="w-full py-3 border-2 border-emerald-600 text-emerald-700 rounded-xl font-medium hover:bg-emerald-50 transition-colors"
          @click="startDemo('skip')"
        >
          Skip to Visit Summary
        </button>
      </div>

      <button
        class="text-sm text-gray-400 hover:text-gray-600 transition-colors"
        @click="switchToDoctor"
      >
        View Doctor Dashboard instead
      </button>
    </div>

    <!-- Demo visit loaded -->
    <div v-else-if="step === 'loaded'" class="max-w-lg mx-auto px-4 py-16 text-center space-y-4">
      <p class="text-emerald-600 font-medium">Demo visit ready</p>
      <h2 class="text-2xl font-bold text-gray-900">Cardiology Visit — PVCs</h2>
      <p class="text-gray-500">Propranolol 40mg 2x/day, with full visit context</p>
      <router-link
        :to="`/visits/${demoVisitId}`"
        class="block w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
      >
        Open Visit Summary
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useApi } from '@/composables/useApi';

const router = useRouter();
const auth = useAuthStore();
const step = ref('welcome');
const demoVisitId = ref(null);
const loading = ref(false);

async function loginDemo(role) {
    const api = useApi();
    const { data } = await api.post('/demo/start', { role });
    const payload = data.data;
    auth.user = payload.user;
    auth.token = payload.token;
    auth.initialized = true;
    return payload.visit;
}

async function startDemo(mode) {
    loading.value = true;
    try {
        const visit = await loginDemo('patient');
        if (mode === 'skip') {
            demoVisitId.value = visit?.id;
            step.value = 'loaded';
        } else {
            router.push({ name: 'companion-scribe' });
        }
    } catch (err) {
        console.error('Demo start failed:', err);
    } finally {
        loading.value = false;
    }
}

async function switchToDoctor() {
    loading.value = true;
    try {
        await loginDemo('doctor');
        router.push({ name: 'doctor-dashboard' });
    } catch (err) {
        console.error('Demo start failed:', err);
    } finally {
        loading.value = false;
    }
}
</script>
