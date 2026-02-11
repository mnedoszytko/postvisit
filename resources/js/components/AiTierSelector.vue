<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-gray-800">AI Intelligence Level</h3>
        <p class="text-xs text-gray-500 mt-0.5">Choose the AI capability tier for your experience</p>
      </div>
      <transition name="fade">
        <span
          v-if="justSwitched"
          class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"
        >
          Switched!
        </span>
      </transition>
    </div>

    <div class="grid grid-cols-3 gap-2">
      <button
        v-for="tier in settings.tiers"
        :key="tier.value"
        :disabled="settings.loading"
        :class="[
          'relative rounded-xl border-2 p-3 text-left transition-all duration-200',
          tier.value === settings.currentTier
            ? tierStyles[tier.value].active
            : 'border-gray-200 hover:border-gray-300 bg-white',
          settings.loading ? 'opacity-60 cursor-wait' : 'cursor-pointer'
        ]"
        @click="switchTier(tier.value)"
      >
        <!-- Tier icon -->
        <div class="flex items-center gap-1.5 mb-1.5">
          <div
            :class="[
              'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold',
              tier.value === settings.currentTier
                ? tierStyles[tier.value].icon
                : 'bg-gray-100 text-gray-400'
            ]"
          >
            {{ tierStyles[tier.value].emoji }}
          </div>
          <span
            :class="[
              'text-xs font-bold',
              tier.value === settings.currentTier ? tierStyles[tier.value].text : 'text-gray-600'
            ]"
          >
            {{ tier.short_label }}
          </span>
        </div>

        <!-- Features list -->
        <ul class="space-y-0.5">
          <li
            v-for="feature in tier.features"
            :key="feature"
            class="text-[10px] leading-tight text-gray-500 flex items-start gap-1"
          >
            <svg class="w-2.5 h-2.5 mt-0.5 shrink-0" :class="tier.value === settings.currentTier ? tierStyles[tier.value].check : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span>{{ feature }}</span>
          </li>
        </ul>

        <!-- Active indicator -->
        <div
          v-if="tier.value === settings.currentTier"
          :class="[
            'absolute top-1.5 right-1.5 w-2 h-2 rounded-full',
            tierStyles[tier.value].dot
          ]"
        />
      </button>
    </div>

    <!-- Model info -->
    <div v-if="settings.activeTier" class="flex items-center gap-2 text-[11px] text-gray-400 pt-1">
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
      </svg>
      <span>Model: <strong>{{ settings.activeTier.model }}</strong></span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSettingsStore } from '@/stores/settings';

const settings = useSettingsStore();
const justSwitched = ref(false);

const tierStyles = {
    good: {
        active: 'border-blue-300 bg-blue-50/50 ring-1 ring-blue-200',
        icon: 'bg-blue-100 text-blue-600',
        text: 'text-blue-700',
        check: 'text-blue-400',
        dot: 'bg-blue-400',
        emoji: 'S',
    },
    better: {
        active: 'border-violet-300 bg-violet-50/50 ring-1 ring-violet-200',
        icon: 'bg-violet-100 text-violet-600',
        text: 'text-violet-700',
        check: 'text-violet-400',
        dot: 'bg-violet-400',
        emoji: 'O',
    },
    opus46: {
        active: 'border-emerald-300 bg-emerald-50/50 ring-1 ring-emerald-200',
        icon: 'bg-emerald-100 text-emerald-600',
        text: 'text-emerald-700',
        check: 'text-emerald-500',
        dot: 'bg-emerald-400 animate-pulse',
        emoji: '4.6',
    },
};

async function switchTier(value) {
    if (value === settings.currentTier || settings.loading) return;
    await settings.setTier(value);
    justSwitched.value = true;
    setTimeout(() => { justSwitched.value = false; }, 2000);
}

onMounted(() => {
    if (!settings.tiers.length) {
        settings.fetchTier();
    }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
