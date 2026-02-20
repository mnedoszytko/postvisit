<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-emerald-50 to-white px-4">
    <div class="text-center max-w-2xl">
      <div class="flex justify-center mb-4">
        <img src="/images/logo-full.png" alt="PostVisit.ai" class="h-14" />
      </div>
      <p class="text-xl text-gray-600 mb-2">
        The bridge between your visit and your health.
      </p>
      <p class="text-gray-500 mb-8">
        AI-powered post-visit companion that helps you understand, remember, and follow through.
      </p>

      <!-- Watch Demo Video -->
      <button
        @click="showVideo = true"
        class="mb-8 inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-700 transition-colors group"
      >
        <span class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 group-hover:bg-emerald-200 transition-colors">
          <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.344-5.891a1.5 1.5 0 000-2.538L6.3 2.841z" />
          </svg>
        </span>
        <span class="font-medium">Watch Demo Video</span>
      </button>

      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <router-link
          to="/login"
          class="px-8 py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors text-lg"
        >
          Try Demo
        </router-link>
        <router-link
          to="/login"
          class="px-8 py-3 border-2 border-emerald-600 text-emerald-700 rounded-xl font-medium hover:bg-emerald-50 transition-colors text-lg"
        >
          Sign In
        </router-link>
      </div>
    </div>

    <footer class="mt-16 text-center max-w-md">
      <p class="text-xs text-gray-400">
        PostVisit.ai helps you understand and act on your doctor's recommendations. It is not a substitute for professional medical judgment.
      </p>
      <p class="mt-3 text-[11px] text-gray-400">
        Built by <a href="https://nedoszytko.com" target="_blank" rel="noopener" class="underline hover:text-gray-600 transition-colors">Michal Nedoszytko</a>
        for the <a href="https://github.com/mnedoszytko/postvisit" target="_blank" rel="noopener" class="font-medium underline hover:text-gray-600 transition-colors">Built with Opus 4.6</a> hackathon by <a href="https://www.anthropic.com" target="_blank" rel="noopener" class="underline hover:text-gray-600 transition-colors">Anthropic</a>
      </p>
    </footer>

    <!-- YouTube Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showVideo"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showVideo = false" />
          <div class="group/modal relative w-full max-w-4xl">
            <button
              @click="showVideo = false"
              class="absolute right-2 top-2 z-10 p-1.5 rounded-full bg-black/40 text-white/60 hover:text-white hover:bg-black/60 opacity-0 group-hover/modal:opacity-100 transition-all duration-200"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <div class="aspect-video rounded-2xl overflow-hidden shadow-2xl ring-1 ring-white/10">
            <iframe
              v-if="showVideo"
              src="https://www.youtube.com/embed/V29UCOii2jE?autoplay=1&rel=0"
              class="w-full h-full"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const showVideo = ref(false)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
