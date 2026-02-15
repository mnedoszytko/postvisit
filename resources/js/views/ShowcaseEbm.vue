<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-6 overflow-hidden">

    <h1 class="showcase-item text-3xl sm:text-4xl font-semibold text-gray-800 text-center max-w-2xl leading-snug tracking-tight" style="--delay: 0">
      Every answer backed by<br>
      <span class="text-emerald-600">Evidence-Based Medicine</span>
    </h1>

    <p class="showcase-item text-gray-400 text-center max-w-lg mt-6 leading-relaxed" style="--delay: 1">
      PubMed guidelines, clinical references, and curated medical databases — so the AI never guesses
    </p>

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
