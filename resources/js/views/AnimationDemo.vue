<template>
  <div class="min-h-screen bg-gray-950 text-white p-8">
    <div class="max-w-4xl mx-auto space-y-8">
      <div>
        <h1 class="text-3xl font-bold">Recording Animation Variants</h1>
        <p class="text-gray-400 mt-2">Three visualizer options for the Companion Scribe recording screen.</p>
      </div>

      <!-- Audio Level Simulator -->
      <div class="bg-gray-900 rounded-xl p-4 flex items-center gap-4">
        <label class="text-sm text-gray-400 shrink-0">Simulate Audio Level:</label>
        <input
          v-model.number="audioLevel"
          type="range"
          min="0"
          max="1"
          step="0.01"
          class="flex-1 accent-emerald-500"
        />
        <span class="text-sm text-emerald-400 font-mono w-12 text-right">{{ audioLevel.toFixed(2) }}</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Variant 1: Three.js Particles -->
        <div class="bg-gray-900 rounded-2xl p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-lg">Particles</h2>
            <span class="text-xs bg-violet-500/20 text-violet-300 px-2 py-0.5 rounded-full">Three.js</span>
          </div>
          <div class="flex justify-center">
            <ParticleVisualizer :size="180" :audio-level="audioLevel" />
          </div>
          <p class="text-xs text-gray-500">300 particles in a sphere that expands/contracts with audio level. Additive blending for glow effect.</p>
        </div>

        <!-- Variant 2: Canvas Waveform -->
        <div class="bg-gray-900 rounded-2xl p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-lg">Waveform</h2>
            <span class="text-xs bg-blue-500/20 text-blue-300 px-2 py-0.5 rounded-full">Canvas 2D</span>
          </div>
          <div class="flex justify-center">
            <WaveformVisualizer :width="220" :height="160" :audio-level="audioLevel" />
          </div>
          <p class="text-xs text-gray-500">48-bar frequency visualizer with gradient fill and center glow. Smooth interpolation.</p>
        </div>

        <!-- Variant 3: CSS-only Pulse -->
        <div class="bg-gray-900 rounded-2xl p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-lg">Pulse</h2>
            <span class="text-xs bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full">CSS-only</span>
          </div>
          <div class="flex justify-center">
            <PulseVisualizer :size="180" :audio-level="audioLevel" />
          </div>
          <p class="text-xs text-gray-500">Zero dependencies. Concentric rings pulse with audio. Lightest option for low-end devices.</p>
        </div>
      </div>

      <!-- Comparison notes -->
      <div class="bg-gray-900 rounded-xl p-6 space-y-3">
        <h3 class="font-semibold">Comparison</h3>
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-emerald-400 font-medium">Particles</p>
            <ul class="text-gray-400 space-y-1 mt-1">
              <li>+ Most visually impressive</li>
              <li>+ 3D depth effect</li>
              <li>- Requires Three.js (heavy)</li>
              <li>- GPU intensive</li>
            </ul>
          </div>
          <div>
            <p class="text-emerald-400 font-medium">Waveform</p>
            <ul class="text-gray-400 space-y-1 mt-1">
              <li>+ Familiar audio UI pattern</li>
              <li>+ Medium weight</li>
              <li>- Canvas only (no CSS)</li>
              <li>- Needs requestAnimationFrame</li>
            </ul>
          </div>
          <div>
            <p class="text-emerald-400 font-medium">Pulse</p>
            <ul class="text-gray-400 space-y-1 mt-1">
              <li>+ Zero dependencies</li>
              <li>+ Lightest on battery</li>
              <li>+ Works everywhere</li>
              <li>- Simplest visually</li>
            </ul>
          </div>
        </div>
      </div>

      <router-link to="/demo" class="inline-block text-sm text-emerald-400 hover:text-emerald-300">
        Back to Demo
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ParticleVisualizer from '@/components/ParticleVisualizer.vue';
import WaveformVisualizer from '@/components/WaveformVisualizer.vue';
import PulseVisualizer from '@/components/PulseVisualizer.vue';

const audioLevel = ref(0.4);
</script>
