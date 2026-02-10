<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-emerald-50 to-white px-4">
    <div class="text-center max-w-2xl">
      <h1 class="text-5xl font-bold text-emerald-800 mb-4">
        PostVisit.ai
      </h1>
      <p class="text-xl text-gray-600 mb-2">
        The bridge between your visit and your health.
      </p>
      <p class="text-gray-500 mb-10">
        AI-powered post-visit companion that helps you understand, remember, and follow through.
      </p>

      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <router-link
          to="/demo"
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

    <p class="mt-16 text-xs text-gray-400 max-w-md text-center">
      PostVisit.ai is a patient education tool. It does not provide medical advice and does not replace professional medical judgment.
    </p>
  </div>
</template>
