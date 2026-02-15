<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-6 py-16 overflow-hidden">

    <div class="showcase-item" style="--delay: 0">
      <img src="/images/logo-full.png" alt="PostVisit.ai" class="h-10 mb-2" />
    </div>

    <p class="showcase-item text-gray-400 text-sm tracking-wide mb-12" style="--delay: 1">
      Built for the real world
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-14 gap-y-6 max-w-2xl w-full">

      <div class="showcase-item tech-row" style="--delay: 2">
        <span class="tech-label">Agentic AI</span>
        <span class="tech-value">Claude Opus 4.6 · 1M token context · extended thinking</span>
      </div>

      <div class="showcase-item tech-row" style="--delay: 3">
        <span class="tech-label">API & Skills</span>
        <span class="tech-value">40+ REST endpoints · agent skills · MCP tools</span>
      </div>

      <div class="showcase-item tech-row" style="--delay: 4">
        <span class="tech-label">Streaming</span>
        <span class="tech-value">Real-time SSE · vision analysis · multi-modal</span>
      </div>

      <div class="showcase-item tech-row" style="--delay: 5">
        <span class="tech-label">Privacy</span>
        <span class="tech-value">HIPAA-ready · consent-first · full audit logging</span>
      </div>

      <div class="showcase-item tech-row" style="--delay: 6">
        <span class="tech-label">EBM</span>
        <span class="tech-value">PubMed · clinical guidelines · curated references</span>
      </div>

      <div class="showcase-item tech-row" style="--delay: 7">
        <span class="tech-label">Physician-Designed</span>
        <span class="tech-value">Clinical experience translated into AI — built by HCP, for patients</span>
      </div>

    </div>

  </div>
</template>

<style scoped>
.showcase-item {
  opacity: 0;
  transform: translateY(20px);
  animation: reveal 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  animation-delay: calc(var(--delay) * 0.3s + 0.3s);
}

@keyframes reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tech-row {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgb(229 231 235 / 0.7);
}

.tech-label {
  font-weight: 600;
  font-size: 0.8125rem;
  color: #059669;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.tech-value {
  font-size: 0.875rem;
  color: #4b5563;
}
</style>
