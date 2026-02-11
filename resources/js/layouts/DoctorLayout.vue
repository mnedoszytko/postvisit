<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Mobile header -->
    <header class="lg:hidden bg-white border-b border-indigo-200 sticky top-0 z-40">
      <div class="px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <router-link to="/doctor" class="text-xl font-semibold text-indigo-700">
            PostVisit.ai
          </router-link>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
            Doctor Panel
          </span>
        </div>
        <button
          class="p-2 text-gray-600 hover:text-indigo-700 transition-colors"
          @click="mobileOpen = !mobileOpen"
        >
          <svg v-if="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile dropdown -->
      <div v-if="mobileOpen" class="border-t border-indigo-100 bg-white">
        <div class="px-4 py-3 space-y-1">
          <div v-if="auth.user" class="flex items-center gap-3 px-3 py-2 mb-1 border-b border-gray-100">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
              {{ initials }}
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ auth.user.name }}</p>
              <p class="text-xs text-indigo-600 font-medium">Doctor Panel</p>
            </div>
          </div>
          <router-link
            to="/doctor"
            class="block px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
            active-class="bg-indigo-50 text-indigo-700 font-medium"
            @click="mobileOpen = false"
          >
            Dashboard
          </router-link>
          <button
            class="w-full text-left px-3 py-2 rounded-lg text-sm text-gray-400 hover:text-red-500 transition-colors"
            @click="handleLogout"
          >
            Log Out
          </button>
        </div>
      </div>
    </header>

    <div class="flex">
      <!-- Desktop sidebar -->
      <aside class="hidden lg:flex w-64 bg-white border-r border-indigo-200 flex-col shrink-0 sticky top-0 h-screen">
        <div class="h-16 flex items-center justify-between px-6 border-b border-indigo-200">
          <router-link to="/doctor" class="text-xl font-semibold text-indigo-700">
            PostVisit.ai
          </router-link>
        </div>

        <div class="px-4 pt-4 pb-2">
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
            Doctor Panel
          </span>
        </div>

        <nav class="flex-1 p-4 space-y-1">
          <router-link
            to="/doctor"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
            active-class="bg-indigo-50 text-indigo-700 font-medium"
          >
            Dashboard
          </router-link>
        </nav>

        <div class="p-4 border-t border-indigo-200">
          <div v-if="auth.user" class="flex items-center gap-3 px-3 py-2 mb-2">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
              {{ initials }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ auth.user.name }}</p>
              <p class="text-xs text-indigo-600 font-medium">Doctor</p>
            </div>
          </div>
          <button
            class="w-full text-left text-sm text-gray-400 hover:text-red-500 transition-colors px-3 py-2"
            @click="handleLogout"
          >
            Log Out
          </button>
        </div>
      </aside>

      <!-- Main content -->
      <main class="flex-1 p-4 lg:p-8 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const mobileOpen = ref(false);

const initials = computed(() => {
    if (!auth.user?.name) return '?';
    return auth.user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
});

async function handleLogout() {
    mobileOpen.value = false;
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>
