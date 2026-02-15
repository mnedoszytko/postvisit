<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-6 py-16 overflow-hidden">

    <!-- Logo -->
    <div class="showcase-item" style="--delay: 0">
      <img src="/images/logo-full.png" alt="PostVisit.ai" class="h-12 mb-2" />
    </div>

    <!-- Tagline -->
    <p class="showcase-item text-gray-400 text-sm tracking-wide mb-14" style="--delay: 1">
      Your AI-powered post-visit companion
    </p>

    <!-- Features grid: 3 left, 3 right -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-16 gap-y-8 max-w-3xl w-full">

      <div class="showcase-item feature-card" style="--delay: 2">
        <div class="feature-bullet">
          <img src="/images/logo-icon.png" alt="" class="w-7 h-auto" />
        </div>
        <div>
          <h3 class="feature-title">Reverse AI Scribe</h3>
          <p class="feature-desc">Ambient recording from your phone — automatically structured into clinical SOAP notes</p>
        </div>
      </div>

      <div class="showcase-item feature-card" style="--delay: 3">
        <div class="feature-bullet">
          <img src="/images/logo-icon.png" alt="" class="w-7 h-auto" />
        </div>
        <div>
          <h3 class="feature-title">Your Health Companion</h3>
          <p class="feature-desc">Discuss every detail of your health from the moment you walk out of the doctor's office</p>
        </div>
      </div>

      <div class="showcase-item feature-card" style="--delay: 4">
        <div class="feature-bullet">
          <img src="/images/logo-icon.png" alt="" class="w-7 h-auto" />
        </div>
        <div>
          <h3 class="feature-title">Labs &amp; Vitals</h3>
          <p class="feature-desc">Connected to wearables and external sources — lab results and vital signs tracked over time</p>
        </div>
      </div>

      <div class="showcase-item feature-card" style="--delay: 5">
        <div class="feature-bullet">
          <img src="/images/logo-icon.png" alt="" class="w-7 h-auto" />
        </div>
        <div>
          <h3 class="feature-title">Tap-to-Explain Terms</h3>
          <p class="feature-desc">Medical terminology highlighted and translated to plain language on tap</p>
        </div>
      </div>

      <div class="showcase-item feature-card" style="--delay: 6">
        <div class="feature-bullet">
          <img src="/images/logo-icon.png" alt="" class="w-7 h-auto" />
        </div>
        <div>
          <h3 class="feature-title">Guidelines &amp; Reference</h3>
          <p class="feature-desc">PubMed, clinical guidelines, and a curated personal database of related evidence</p>
        </div>
      </div>

      <div class="showcase-item feature-card" style="--delay: 7">
        <div class="feature-bullet">
          <img src="/images/logo-icon.png" alt="" class="w-7 h-auto" />
        </div>
        <div>
          <h3 class="feature-title">Full Health Context</h3>
          <p class="feature-desc">Transcript, notes, medications, labs, vitals, conditions, guidelines, history — everything in a single 1M-token AI context</p>
        </div>
      </div>

    </div>

    <!-- Footer tagline -->
    <p class="showcase-item mt-16 text-xs text-gray-300 tracking-widest uppercase" style="--delay: 8">
      Built for patients &middot; Opus 4.6 Hackathon 2026
    </p>
  </div>
</template>

<style scoped>
/* Staggered entrance animation */
.showcase-item {
  opacity: 0;
  transform: translateY(24px);
  animation: reveal 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  animation-delay: calc(var(--delay) * 0.4s + 0.5s);
}

@keyframes reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Feature card */
.feature-card {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
}

.feature-bullet {
  flex-shrink: 0;
  margin-top: 0.125rem;
  opacity: 0.8;
}

.feature-title {
  font-weight: 600;
  font-size: 1.0625rem;
  color: #1f2937;
  letter-spacing: -0.01em;
  line-height: 1.3;
}

.feature-desc {
  font-size: 0.8125rem;
  color: #6b7280;
  line-height: 1.55;
  margin-top: 0.125rem;
}
</style>
