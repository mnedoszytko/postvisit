<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
      <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <router-link to="/doctor" class="text-xl font-semibold text-emerald-700">
          PostVisit.ai
        </router-link>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <router-link
          to="/doctor"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
          active-class="bg-emerald-50 text-emerald-700 font-medium"
        >
          Dashboard
        </router-link>
      </nav>

      <div class="p-4 border-t border-gray-200">
        <button
          class="w-full text-left text-sm text-gray-400 hover:text-red-500 transition-colors px-3 py-2"
          @click="handleLogout"
        >
          Log Out
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-8 overflow-y-auto">
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
