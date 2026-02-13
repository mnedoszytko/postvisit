<template>
  <div class="bg-gray-50 rounded-xl px-4 py-3 select-none">
    <div class="flex items-center gap-3">
      <!-- Play/Pause -->
      <button
        class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 transition-colors"
        :class="playing ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'"
        @click="togglePlay"
      >
        <svg v-if="playing" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
        </svg>
        <svg v-else class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M8 5v14l11-7z" />
        </svg>
      </button>

      <!-- Time current -->
      <span class="text-xs font-mono text-gray-600 w-10 text-right shrink-0">{{ formatTime(currentTime) }}</span>

      <!-- Progress bar -->
      <div
        ref="trackRef"
        class="flex-1 h-1.5 bg-gray-300 rounded-full cursor-pointer relative touch-none"
        @pointerdown="onSeekStart"
      >
        <!-- Played portion -->
        <div
          class="h-full bg-emerald-600 rounded-full pointer-events-none"
          :style="{ width: progressPercent + '%' }"
        />
        <!-- Thumb -->
        <div
          class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 bg-white border-2 border-emerald-600 rounded-full shadow transition-transform pointer-events-none"
          :class="dragging ? 'scale-125' : ''"
          :style="{ left: progressPercent + '%' }"
        />
      </div>

      <!-- Time total -->
      <span class="text-xs font-mono text-gray-600 w-10 shrink-0">{{ formatTime(totalDuration) }}</span>

      <!-- Speed button -->
      <button
        class="text-[10px] font-bold text-gray-400 hover:text-emerald-600 transition-colors px-1"
        @click="cycleSpeed"
        :title="`Playback speed: ${playbackRate}x`"
      >
        {{ playbackRate }}x
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    src: { type: String, required: true },
    knownDuration: { type: Number, default: 0 },
});

const audio = new Audio();
audio.preload = 'metadata';

const playing = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const playbackRate = ref(1);
const trackRef = ref(null);
const dragging = ref(false);
let rafId = null;
let fixingDuration = false;

const totalDuration = computed(() => {
    // Browser-detected duration (most accurate when available)
    if (duration.value && isFinite(duration.value) && duration.value > 1) {
        return duration.value;
    }
    // Fall back to known duration from transcript DB field
    if (props.knownDuration && props.knownDuration > 1) {
        return props.knownDuration;
    }
    return 0;
});

const progressPercent = computed(() => {
    if (!totalDuration.value) return 0;
    return Math.min((currentTime.value / totalDuration.value) * 100, 100);
});

function formatTime(sec) {
    if (!sec || !isFinite(sec)) return '0:00';
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function togglePlay() {
    if (playing.value) {
        audio.pause();
    } else {
        audio.play();
    }
}

const speeds = [1, 1.25, 1.5, 2, 0.75];
function cycleSpeed() {
    const idx = speeds.indexOf(playbackRate.value);
    playbackRate.value = speeds[(idx + 1) % speeds.length];
    audio.playbackRate = playbackRate.value;
}

function updateTime() {
    if (!dragging.value) {
        currentTime.value = audio.currentTime;
    }
    if (playing.value) {
        rafId = requestAnimationFrame(updateTime);
    }
}

// --- Seek / drag ---
function onSeekStart(e) {
    e.preventDefault();
    dragging.value = true;
    seekFromEvent(e);
    // Apply immediately on click
    audio.currentTime = currentTime.value;

    const onMove = (ev) => {
        ev.preventDefault();
        seekFromEvent(ev);
    };
    const onUp = (ev) => {
        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);
        // Apply final seek position
        if (totalDuration.value) {
            audio.currentTime = currentTime.value;
        }
        dragging.value = false;
    };
    document.addEventListener('pointermove', onMove);
    document.addEventListener('pointerup', onUp);
}

function seekFromEvent(e) {
    if (!trackRef.value || !totalDuration.value) return;
    const rect = trackRef.value.getBoundingClientRect();
    const ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    currentTime.value = ratio * totalDuration.value;
}

// Audio events
audio.addEventListener('play', () => {
    playing.value = true;
    rafId = requestAnimationFrame(updateTime);
});
audio.addEventListener('pause', () => {
    playing.value = false;
    cancelAnimationFrame(rafId);
});
audio.addEventListener('ended', () => {
    playing.value = false;
    cancelAnimationFrame(rafId);
    currentTime.value = 0;
});
audio.addEventListener('loadedmetadata', () => {
    if (audio.duration === Infinity || isNaN(audio.duration)) {
        // WebM from MediaRecorder often lacks duration metadata.
        // Seeking to a large time forces the browser to calculate the real duration.
        fixingDuration = true;
        audio.currentTime = 1e10;
    } else {
        duration.value = audio.duration;
    }
});
audio.addEventListener('durationchange', () => {
    if (isFinite(audio.duration) && audio.duration > 0) {
        duration.value = audio.duration;
    }
});
audio.addEventListener('seeked', () => {
    if (fixingDuration) {
        // The seek-to-end trick completed — browser clamped to actual end.
        // Duration should now be available.
        fixingDuration = false;
        if (isFinite(audio.duration) && audio.duration > 0) {
            duration.value = audio.duration;
        }
        audio.currentTime = 0;
        currentTime.value = 0;
    }
});
audio.addEventListener('timeupdate', () => {
    if (!dragging.value && !fixingDuration) {
        currentTime.value = audio.currentTime;
    }
});

watch(() => props.src, (newSrc) => {
    audio.src = newSrc;
}, { immediate: true });

onUnmounted(() => {
    cancelAnimationFrame(rafId);
    audio.pause();
    audio.src = '';
});
</script>
