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
});

const SIZE = 480;
const displaySize = computed(() => props.size);
const canvas = ref(null);

let renderer, scene, camera, animId, audioInterval;
let audioLevel = 0;
let audioTarget = 0;
let time = 0;

function fakeAudio() {
  audioTarget = 0.2 + Math.random() * 0.8;
}

function initThree() {
  const c = canvas.value;
  if (!c) return;
  renderer = new THREE.WebGLRenderer({ canvas: c, alpha: true, antialias: true });
  renderer.setSize(SIZE, SIZE);
  renderer.setPixelRatio(2);
  renderer.setClearColor(0x000000, 0);
}

function buildTerrain() {
  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(50, 1, 0.1, 100);
  camera.position.set(0, 2.5, 4.5);
  camera.lookAt(0, -0.3, 0);

  scene.add(new THREE.AmbientLight(0x2d6b3f, 0.5));

  const light = new THREE.DirectionalLight(0x6ee7a8, 0.8);
  light.position.set(2, 4, 2);
  scene.add(light);

  const glow = new THREE.PointLight(0x34d399, 1.5, 12);
  glow.position.set(0, 1, 0);
  scene.add(glow);

  const seg = 64;
  const geo = new THREE.PlaneGeometry(7, 7, seg, seg);
  geo.rotateX(-Math.PI / 2);

  const mat = new THREE.MeshStandardMaterial({
    color: 0x10b981,
    wireframe: true,
    transparent: true,
    opacity: 0.35,
  });

  const mesh = new THREE.Mesh(geo, mat);
  scene.add(mesh);
  const posAttr = geo.getAttribute('position');

  return { mesh, posAttr, glow };
}

function start() {
  if (!renderer) return;
  const { posAttr, glow } = buildTerrain();

  audioInterval = setInterval(fakeAudio, 120);

  function loop(t) {
    time = t;
    audioLevel += (audioTarget - audioLevel) * 0.12;

    const a = audioLevel * 0.6;
    for (let i = 0; i < posAttr.count; i++) {
      const x = posAttr.getX(i);
      const z = posAttr.getZ(i);
      const wave = Math.sin(x * 0.8 + time * 0.0008) * Math.cos(z * 0.8 + time * 0.0006);
      const ripple = Math.sin(x * 2 + time * 0.0012) * Math.sin(z * 1.5 + time * 0.001) * 0.25;
      posAttr.setY(i, (wave + ripple) * (0.15 + a));
    }
    posAttr.needsUpdate = true;
    glow.intensity = 1 + audioLevel * 1.5;

    renderer.render(scene, camera);
    animId = requestAnimationFrame(loop);
  }

  animId = requestAnimationFrame(loop);
}

function teardown() {
  if (audioInterval) clearInterval(audioInterval);
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
}

onMounted(() => {
  initThree();
  start();
});

onUnmounted(() => {
  teardown();
  if (renderer) { renderer.dispose(); renderer = null; }
});
</script>
