<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-6 overflow-hidden">

    <h1 class="showcase-item text-3xl sm:text-4xl font-semibold text-gray-800 text-center max-w-2xl leading-snug tracking-tight" style="--delay: 0">
      But how to do it<br>
      if you have <span class="text-emerald-600">no EHR connection</span>?
    </h1>

  </div>
</template>

<style scoped>
.showcase-item {
  opacity: 0;
  transform: translateY(24px);
  animation: reveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  animation-delay: calc(var(--delay) * 0.5s + 0.4s);
}

@keyframes reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
