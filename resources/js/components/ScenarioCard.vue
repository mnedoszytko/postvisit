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
      <!-- Skeleton pulse (visible until media loads) -->
      <div
        v-if="!mediaLoaded && (scenario.animation_url || scenario.photo_url)"
        class="absolute inset-0 bg-gradient-to-br from-gray-200 via-gray-100 to-gray-200 animate-pulse"
      >
        <div class="absolute inset-0 flex items-center justify-center">
          <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
          </svg>
        </div>
      </div>

      <!-- Video: preloaded paused on 1st frame, plays on hover -->
      <video
        v-if="scenario.animation_url"
        ref="videoEl"
        :src="scenario.animation_url"
        :class="['w-full h-full object-cover transition-opacity duration-300', mediaLoaded ? 'opacity-100' : 'opacity-0']"
        muted
        playsinline
        preload="auto"
        @loadeddata="mediaLoaded = true"
      />
      <!-- Static photo fallback (no animation available) -->
      <img
        v-else-if="scenario.photo_url"
        :src="scenario.photo_url"
        :alt="scenario.patient_name"
        :class="['w-full h-full object-cover group-hover:scale-105 transition-all duration-300', mediaLoaded ? 'opacity-100' : 'opacity-0']"
        loading="lazy"
        @load="mediaLoaded = true"
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
        v-if="scenario.language && mediaLoaded"
        class="absolute top-2 right-2 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-black/50 text-white"
      >
        {{ scenario.language }}
      </span>
      <!-- Specialty badge -->
      <span
        v-if="scenario.specialty && mediaLoaded"
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
const mediaLoaded = ref(false);

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
