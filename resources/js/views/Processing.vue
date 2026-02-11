<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-emerald-50 to-white px-4">
    <div class="text-center space-y-6">
      <LoadingParticles />
      <h1 class="text-2xl font-semibold text-gray-800">Analyzing your visit...</h1>
      <p class="text-gray-500">Our AI is processing your visit data to create a personalized summary.</p>
    </div>
  </div>
</template>

<script setup>
import LoadingParticles from '@/components/LoadingParticles.vue';
</script>
