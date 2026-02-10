<template>
  <div class="relative w-48 h-48 mx-auto">
    <div
      v-for="i in 12"
      :key="i"
      class="absolute w-3 h-3 bg-emerald-400 rounded-full animate-ping"
      :style="{
        top: `${50 + 40 * Math.sin((i * 2 * Math.PI) / 12)}%`,
        left: `${50 + 40 * Math.cos((i * 2 * Math.PI) / 12)}%`,
        animationDelay: `${i * 0.15}s`,
        animationDuration: '1.5s',
        opacity: 0.6,
      }"
    />
    <div class="absolute inset-0 flex items-center justify-center">
      <div class="w-16 h-16 bg-emerald-500 rounded-full animate-pulse opacity-30" />
    </div>
  </div>
</template>
