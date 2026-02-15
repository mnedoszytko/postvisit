<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-50 to-white flex flex-col items-center justify-center px-6 overflow-hidden">

    <div class="showcase-item" style="--delay: 0">
      <img src="/images/logo-full.png" alt="PostVisit.ai" class="h-16 sm:h-20" />
    </div>

    <div class="showcase-item w-16 h-px bg-emerald-300/60 my-6" style="--delay: 1"></div>

    <h1 class="showcase-item text-2xl sm:text-3xl font-medium text-gray-700 text-center tracking-tight" style="--delay: 2">
      The bridge between your visit and your health.
    </h1>

    <p class="showcase-item text-gray-400 text-center mt-4" style="--delay: 3">
      AI-powered post-visit companion.
    </p>

  </div>
</template>

<style scoped>
.showcase-item {
  opacity: 0;
  transform: translateY(20px);
  animation: reveal 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  animation-delay: calc(var(--delay) * 0.45s + 0.3s);
}

@keyframes reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
