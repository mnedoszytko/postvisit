<template>
  <div class="w-40 h-40 mx-auto relative flex items-center justify-center">
    <!-- Variant 1: Concentric Ripples -->
    <template v-if="variant === 'ripples'">
      <div class="absolute inset-0 flex items-center justify-center">
        <div class="absolute w-36 h-36 rounded-full bg-red-200 animate-ping opacity-20" style="animation-duration: 2s;" />
        <div class="absolute w-28 h-28 rounded-full bg-red-300 animate-ping opacity-30" style="animation-duration: 1.5s; animation-delay: 0.3s;" />
        <div class="absolute w-20 h-20 rounded-full bg-red-400 animate-ping opacity-40" style="animation-duration: 1s; animation-delay: 0.6s;" />
        <div class="w-12 h-12 rounded-full bg-red-500 shadow-lg shadow-red-500/50 z-10 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-6 h-6">
            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
          </svg>
        </div>
      </div>
    </template>

    <!-- Variant 2: Audio Waveform Bars -->
    <template v-else-if="variant === 'waveform'">
      <div class="flex items-end gap-1 h-24">
        <div
          v-for="i in 9"
          :key="i"
          class="w-2.5 bg-gradient-to-t from-red-500 to-red-300 rounded-full"
          :style="{
            height: barHeights[i - 1] + '%',
            transition: 'height 0.15s ease',
          }"
        />
      </div>
    </template>

    <!-- Variant 3: Glowing Orbit -->
    <template v-else-if="variant === 'orbit'">
      <div class="relative w-32 h-32">
        <!-- Outer ring -->
        <div class="absolute inset-0 rounded-full border-2 border-red-200 animate-spin" style="animation-duration: 6s;">
          <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-red-400" />
        </div>
        <!-- Middle ring -->
        <div class="absolute inset-3 rounded-full border-2 border-red-300 animate-spin" style="animation-duration: 4s; animation-direction: reverse;">
          <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-2.5 h-2.5 rounded-full bg-red-500" />
        </div>
        <!-- Inner glow -->
        <div class="absolute inset-8 rounded-full bg-red-500 animate-pulse shadow-lg shadow-red-500/50 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" class="w-8 h-8">
            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
            <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
          </svg>
        </div>
      </div>
    </template>
    <!-- Variant 4: 3D Particle Cloud (Apple Watch style) -->
    <template v-else-if="variant === 'particles'">
      <canvas
        ref="particleCanvas"
        class="w-40 h-40"
        width="320"
        height="320"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'ripples',
        validator: (v) => ['ripples', 'waveform', 'orbit', 'particles'].includes(v),
    },
});

const barHeights = ref([30, 50, 70, 40, 90, 60, 80, 45, 35]);
let animFrame = null;

function animateBars() {
    barHeights.value = barHeights.value.map(() => 20 + Math.random() * 80);
    animFrame = setTimeout(animateBars, 150);
}

// --- Particle cloud ---
const particleCanvas = ref(null);
let particleRaf = null;
let particles = [];
let audioLevel = 0;
let audioLevelTarget = 0;

function initParticles() {
  const count = 120;
  particles = [];
  for (let i = 0; i < count; i++) {
    // Distribute on a sphere surface
    const theta = Math.random() * Math.PI * 2;
    const phi = Math.acos(2 * Math.random() - 1);
    const r = 0.6 + Math.random() * 0.4; // radius 0.6-1.0
    particles.push({
      theta,
      phi,
      r,
      baseR: r,
      speed: 0.2 + Math.random() * 0.6,
      size: 1 + Math.random() * 2.5,
      hue: 0 + Math.random() * 30, // red-orange range
    });
  }
}

function drawParticles(time) {
  const canvas = particleCanvas.value;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const w = canvas.width;
  const h = canvas.height;
  const cx = w / 2;
  const cy = h / 2;

  // Smooth audio level
  audioLevel += (audioLevelTarget - audioLevel) * 0.1;

  ctx.clearRect(0, 0, w, h);

  // Sort by depth for correct overlapping
  const projected = particles.map((p) => {
    const t = time * 0.001 * p.speed;
    const theta = p.theta + t * 0.3;
    const phi = p.phi + Math.sin(t * 0.5) * 0.1;

    // Audio-reactive radius expansion
    const expand = 1 + audioLevel * 0.5;
    const r = (p.baseR + audioLevel * 0.3 * Math.sin(time * 0.005 + p.theta * 3)) * expand;
    const radius = r * 55; // scale to canvas

    const x = radius * Math.sin(phi) * Math.cos(theta);
    const y = radius * Math.sin(phi) * Math.sin(theta);
    const z = radius * Math.cos(phi);

    // Simple 3D perspective
    const perspective = 300;
    const scale = perspective / (perspective + z);

    return {
      sx: cx + x * scale,
      sy: cy + y * scale,
      scale,
      z,
      size: p.size * scale,
      hue: p.hue,
      alpha: 0.3 + 0.7 * scale,
    };
  });

  projected.sort((a, b) => a.z - b.z);

  for (const p of projected) {
    const glow = audioLevel * 0.4;
    ctx.beginPath();
    ctx.arc(p.sx, p.sy, p.size + glow, 0, Math.PI * 2);
    ctx.fillStyle = `hsla(${p.hue}, 85%, ${55 + audioLevel * 15}%, ${p.alpha})`;
    ctx.fill();

    // Glow effect
    if (p.size > 1.5) {
      ctx.beginPath();
      ctx.arc(p.sx, p.sy, (p.size + glow) * 2.5, 0, Math.PI * 2);
      ctx.fillStyle = `hsla(${p.hue}, 90%, 60%, ${p.alpha * 0.1})`;
      ctx.fill();
    }
  }

  particleRaf = requestAnimationFrame(drawParticles);
}

function simulateAudio() {
  // Simulate audio reactivity with organic noise
  audioLevelTarget = 0.3 + Math.random() * 0.7;
  animFrame = setTimeout(simulateAudio, 100 + Math.random() * 200);
}

function startParticleAnimation() {
  initParticles();
  // Wait a tick for canvas ref
  requestAnimationFrame(() => {
    particleRaf = requestAnimationFrame(drawParticles);
  });
  simulateAudio();
}

function stopParticleAnimation() {
  if (particleRaf) {
    cancelAnimationFrame(particleRaf);
    particleRaf = null;
  }
}

watch(() => props.variant, (val, old) => {
  if (old === 'particles') stopParticleAnimation();
  if (val === 'particles') {
    // nextTick equivalent
    requestAnimationFrame(startParticleAnimation);
  }
});

onMounted(() => {
    animateBars();
    if (props.variant === 'particles') {
      startParticleAnimation();
    }
});

onUnmounted(() => {
    clearTimeout(animFrame);
    stopParticleAnimation();
});
</script>
