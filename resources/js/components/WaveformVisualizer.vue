<template>
  <div class="relative flex items-center justify-center">
    <div class="rounded-2xl overflow-hidden shadow-lg shadow-emerald-500/20" :style="{ width: displayWidth + 'px', height: displayHeight + 'px' }">
      <canvas ref="canvas" :width="WIDTH" :height="HEIGHT" :style="{ width: displayWidth + 'px', height: displayHeight + 'px' }" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  width: { type: Number, default: 280 },
  height: { type: Number, default: 120 },
  audioLevel: { type: Number, default: 0 },
});

const WIDTH = 560;
const HEIGHT = 240;
const displayWidth = computed(() => props.width);
const displayHeight = computed(() => props.height);
const canvas = ref(null);

let animId;
let ctx;
const bars = 48;
const barHeights = new Float32Array(bars);
const barTargets = new Float32Array(bars);

function randomizeTargets() {
  for (let i = 0; i < bars; i++) {
    const centerFactor = 1 - Math.abs(i - bars / 2) / (bars / 2);
    barTargets[i] = (0.1 + Math.random() * 0.9) * (0.3 + centerFactor * 0.7);
  }
}

function animate(t) {
  if (!ctx) return;

  const level = props.audioLevel || (0.2 + Math.sin(t * 0.003) * 0.3 + Math.sin(t * 0.007) * 0.1);

  // Randomize targets periodically
  if (Math.floor(t / 80) % 2 === 0) {
    randomizeTargets();
  }

  ctx.clearRect(0, 0, WIDTH, HEIGHT);

  const barWidth = (WIDTH - (bars - 1) * 2) / bars;
  const maxBarHeight = HEIGHT * 0.8;
  const centerY = HEIGHT / 2;

  for (let i = 0; i < bars; i++) {
    // Smooth towards target
    barHeights[i] += (barTargets[i] * level - barHeights[i]) * 0.15;

    const h = barHeights[i] * maxBarHeight;
    const x = i * (barWidth + 2);
    const halfH = h / 2;

    // Gradient from center
    const gradient = ctx.createLinearGradient(x, centerY - halfH, x, centerY + halfH);
    gradient.addColorStop(0, 'rgba(52, 211, 153, 0.9)');
    gradient.addColorStop(0.5, 'rgba(16, 185, 129, 1)');
    gradient.addColorStop(1, 'rgba(52, 211, 153, 0.9)');

    ctx.fillStyle = gradient;
    ctx.beginPath();
    ctx.roundRect(x, centerY - halfH, barWidth, h, 2);
    ctx.fill();
  }

  // Center glow
  const glowRadius = 30 + level * 40;
  const glow = ctx.createRadialGradient(WIDTH / 2, centerY, 0, WIDTH / 2, centerY, glowRadius);
  glow.addColorStop(0, 'rgba(52, 211, 153, 0.15)');
  glow.addColorStop(1, 'rgba(52, 211, 153, 0)');
  ctx.fillStyle = glow;
  ctx.fillRect(0, 0, WIDTH, HEIGHT);

  animId = requestAnimationFrame(animate);
}

onMounted(() => {
  const c = canvas.value;
  if (!c) return;
  ctx = c.getContext('2d');
  randomizeTargets();
  animId = requestAnimationFrame(animate);
});

onUnmounted(() => {
  if (animId) cancelAnimationFrame(animId);
});
</script>
