<template>
  <div
    @mouseenter="onHover"
    @mouseleave="onLeave"
    @touchstart.passive="onTouch"
    @touchend.passive="onLeave"
    @touchcancel.passive="onLeave"
  >
    <!-- Photo / Animation -->
    <div class="aspect-square overflow-hidden bg-gray-200 relative">
      <!-- Video: preloaded paused on 1st frame, plays on hover -->
      <video
        v-if="scenario.animation_url"
        ref="videoEl"
        :src="scenario.animation_url"
        class="w-full h-full object-cover"
        muted
        playsinline
        preload="auto"
      />
      <!-- Static photo fallback (no animation available) -->
      <img
        v-else-if="scenario.photo_url"
        :src="scenario.photo_url"
        :alt="scenario.patient_name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        loading="lazy"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
        <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
        </svg>
      </div>
      <!-- Loading overlay -->
      <div
        v-if="starting"
        class="absolute inset-0 bg-black/40 flex items-center justify-center"
      >
        <div class="w-8 h-8 border-3 border-white/30 border-t-white rounded-full animate-spin"></div>
      </div>
      <!-- Language badge -->
      <span
        v-if="scenario.language"
        class="absolute top-2 right-2 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-black/50 text-white"
      >
        {{ scenario.language }}
      </span>
      <!-- Specialty badge -->
      <span
        v-if="scenario.specialty"
        class="absolute bottom-2 left-2 text-[10px] font-medium capitalize px-1.5 py-0.5 rounded bg-white/90 text-gray-700"
      >
        {{ scenario.specialty }}
      </span>
    </div>

    <!-- Info -->
    <div class="px-3 py-2.5 sm:px-4 sm:py-3">
      <div class="font-bold text-gray-900 text-sm sm:text-base leading-tight">
        {{ String(index + 1).padStart(2, '0') }} — {{ scenario.patient_name }}
      </div>
      <div class="text-xs sm:text-sm text-gray-500 mt-0.5">
        {{ scenario.patient_age }}{{ scenario.patient_gender === 'male' ? 'M' : 'F' }}
        <template v-if="scenario.bmi"> · BMI {{ scenario.bmi }}</template>
      </div>
      <div v-if="scenario.description" class="text-xs sm:text-[13px] text-emerald-600 italic mt-1 line-clamp-2 leading-snug">
        {{ scenario.description }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  scenario: { type: Object, required: true },
  index: { type: Number, required: true },
  starting: { type: Boolean, default: false },
});

const videoEl = ref(null);

function onHover() {
  if (!videoEl.value) return;
  if (videoEl.value.ended) {
    videoEl.value.currentTime = 0;
  }
  videoEl.value.play().catch(() => {});
}

function onTouch() {
  // On mobile, play when finger touches the card (e.g. while scrolling)
  onHover();
}

function onLeave() {
  videoEl.value?.pause();
}
</script>
