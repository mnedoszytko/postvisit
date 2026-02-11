<template>
  <div class="relative flex items-center justify-center">
    <div class="rounded-full overflow-hidden shadow-lg shadow-emerald-500/20" :style="{ width: displaySize + 'px', height: displaySize + 'px' }">
      <canvas ref="canvas" :width="SIZE" :height="SIZE" :style="{ width: displaySize + 'px', height: displaySize + 'px' }" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';

const props = defineProps({
  size: { type: Number, default: 200 },
  audioLevel: { type: Number, default: 0 },
});

const SIZE = 480;
const displaySize = computed(() => props.size);
const canvas = ref(null);

let renderer, scene, camera, animId;
let particles, positions, velocities;
const PARTICLE_COUNT = 300;

function initThree() {
  const c = canvas.value;
  if (!c) return;
  renderer = new THREE.WebGLRenderer({ canvas: c, alpha: true, antialias: true });
  renderer.setSize(SIZE, SIZE);
  renderer.setPixelRatio(2);
  renderer.setClearColor(0x000000, 0);
}

function buildScene() {
  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(60, 1, 0.1, 100);
  camera.position.set(0, 0, 5);

  const geometry = new THREE.BufferGeometry();
  positions = new Float32Array(PARTICLE_COUNT * 3);
  velocities = new Float32Array(PARTICLE_COUNT * 3);

  for (let i = 0; i < PARTICLE_COUNT; i++) {
    const theta = Math.random() * Math.PI * 2;
    const phi = Math.acos(2 * Math.random() - 1);
    const r = 0.8 + Math.random() * 0.8;
    positions[i * 3] = r * Math.sin(phi) * Math.cos(theta);
    positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
    positions[i * 3 + 2] = r * Math.cos(phi);
    velocities[i * 3] = (Math.random() - 0.5) * 0.01;
    velocities[i * 3 + 1] = (Math.random() - 0.5) * 0.01;
    velocities[i * 3 + 2] = (Math.random() - 0.5) * 0.01;
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

  const material = new THREE.PointsMaterial({
    color: 0x34d399,
    size: 0.06,
    transparent: true,
    opacity: 0.8,
    blending: THREE.AdditiveBlending,
  });

  particles = new THREE.Points(geometry, material);
  scene.add(particles);
}

function animate(t) {
  const level = props.audioLevel || (0.2 + Math.sin(t * 0.002) * 0.3);
  const expansion = 1 + level * 1.5;

  for (let i = 0; i < PARTICLE_COUNT; i++) {
    const ix = i * 3;
    const x = positions[ix];
    const y = positions[ix + 1];
    const z = positions[ix + 2];
    const dist = Math.sqrt(x * x + y * y + z * z);
    const targetDist = (0.8 + Math.random() * 0.01) * expansion;

    if (dist > 0.001) {
      const scale = 1 + (targetDist - dist) * 0.02;
      positions[ix] = x * scale + velocities[ix] * level * 2;
      positions[ix + 1] = y * scale + velocities[ix + 1] * level * 2;
      positions[ix + 2] = z * scale + velocities[ix + 2] * level * 2;
    }
  }

  particles.geometry.attributes.position.needsUpdate = true;
  particles.rotation.y = t * 0.0003;
  particles.rotation.x = Math.sin(t * 0.0002) * 0.2;
  particles.material.size = 0.04 + level * 0.06;

  renderer.render(scene, camera);
  animId = requestAnimationFrame(animate);
}

onMounted(() => {
  initThree();
  if (renderer) {
    buildScene();
    animId = requestAnimationFrame(animate);
  }
});

onUnmounted(() => {
  if (animId) cancelAnimationFrame(animId);
  if (scene) {
    scene.traverse((obj) => {
      if (obj.geometry) obj.geometry.dispose();
      if (obj.material) {
        if (Array.isArray(obj.material)) obj.material.forEach(m => m.dispose());
        else obj.material.dispose();
      }
    });
  }
  if (renderer) { renderer.dispose(); renderer = null; }
});
</script>
