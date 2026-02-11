<template>
  <router-link
    v-if="settings.activeTier"
    to="/profile"
    :class="[
      'hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold transition-colors',
      badgeClass
    ]"
    title="AI Intelligence Level — click to change"
  >
    <span :class="['w-1.5 h-1.5 rounded-full', dotClass]" />
    {{ settings.tierLabel }}
  </router-link>
</template>

<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '@/stores/settings';

const settings = useSettingsStore();

const badgeClass = computed(() => {
    switch (settings.currentTier) {
        case 'good': return 'bg-blue-50 text-blue-600 border border-blue-200';
        case 'better': return 'bg-violet-50 text-violet-600 border border-violet-200';
        case 'opus46': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
        default: return 'bg-gray-50 text-gray-600 border border-gray-200';
    }
});

const dotClass = computed(() => {
    switch (settings.currentTier) {
        case 'good': return 'bg-blue-400';
        case 'better': return 'bg-violet-400';
        case 'opus46': return 'bg-emerald-400 animate-pulse';
        default: return 'bg-gray-400';
    }
});
</script>
