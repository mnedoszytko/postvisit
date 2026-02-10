<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
      <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <router-link to="/profile" class="text-xl font-semibold text-emerald-700">
          PostVisit.ai
        </router-link>

        <nav class="flex items-center gap-4">
          <router-link
            to="/profile"
            class="text-sm text-gray-600 hover:text-emerald-700 transition-colors"
          >
            My Health
          </router-link>
          <router-link
            to="/scribe"
            class="text-sm text-gray-600 hover:text-emerald-700 transition-colors"
          >
            Record Visit
          </router-link>
          <button
            class="text-sm text-gray-400 hover:text-red-500 transition-colors"
            @click="handleLogout"
          >
            Log Out
          </button>
        </nav>
      </div>
    </header>

    <!-- Main content -->
    <main class="max-w-4xl mx-auto px-4 py-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>
