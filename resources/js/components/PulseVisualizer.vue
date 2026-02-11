<template>
  <div class="relative flex items-center justify-center" :style="{ width: size + 'px', height: size + 'px' }">
    <!-- Outer rings -->
    <div
      v-for="i in 3"
      :key="'ring-' + i"
      class="absolute rounded-full border-2 border-emerald-400"
      :style="ringStyle(i)"
    />

    <!-- Core pulse -->
    <div
      class="absolute rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50"
      :style="coreStyle"
    />

    <!-- Inner dot -->
    <div class="absolute w-3 h-3 rounded-full bg-white/80" />
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  size: { type: Number, default: 200 },
  audioLevel: { type: Number, default: 0 },
});

const internalLevel = ref(0.3);
let animId;
let time = 0;

function tick() {
  time += 16;
  // Generate fake audio level if none provided
  if (props.audioLevel === 0) {
    internalLevel.value = 0.2 + Math.sin(time * 0.004) * 0.3 + Math.sin(time * 0.007) * 0.15;
  } else {
    internalLevel.value = props.audioLevel;
  }
  animId = requestAnimationFrame(tick);
}

const level = computed(() => Math.max(0, Math.min(1, internalLevel.value)));

function ringStyle(index) {
  const baseSize = props.size * (0.4 + index * 0.15);
  const expansion = 1 + level.value * 0.3 * index;
  const s = baseSize * expansion;
  const delay = index * 0.15;
  return {
    width: s + 'px',
    height: s + 'px',
    opacity: 0.6 - index * 0.15 - level.value * 0.1,
    transition: 'all 0.15s ease-out',
    animationDelay: delay + 's',
  };
}

const coreStyle = computed(() => {
  const baseSize = props.size * 0.25;
  const expansion = 1 + level.value * 0.4;
  const s = baseSize * expansion;
  return {
    width: s + 'px',
    height: s + 'px',
    transition: 'all 0.1s ease-out',
  };
});

onMounted(() => {
  animId = requestAnimationFrame(tick);
});

onUnmounted(() => {
  if (animId) cancelAnimationFrame(animId);
});
</script>
