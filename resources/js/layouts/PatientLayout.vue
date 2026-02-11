<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Demo mode banner -->
    <div v-if="isDemoUser" class="bg-amber-400 text-amber-900 text-sm font-medium sticky top-0 z-50">
      <div class="max-w-4xl mx-auto px-4 h-10 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>Demo Mode</span>
          <span class="hidden sm:inline text-amber-700">&mdash; {{ auth.user?.name }}</span>
        </div>
        <router-link
          to="/demo/scenarios"
          class="px-3 py-1 bg-amber-900/10 hover:bg-amber-900/20 rounded-lg text-xs font-semibold transition-colors"
        >
          Switch Scenario
        </router-link>
      </div>
    </div>

    <!-- Header -->
    <header class="bg-white border-b border-emerald-200 sticky top-0 z-40">
      <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <router-link to="/profile" class="text-xl font-semibold text-emerald-700">
            PostVisit.ai
          </router-link>
          <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
            Patient Panel
          </span>
        </div>

        <!-- Desktop nav -->
        <nav class="hidden md:flex items-center gap-4">
          <router-link
            to="/profile"
            class="text-sm text-gray-600 hover:text-emerald-700 transition-colors"
          >
            Profile
          </router-link>
          <router-link
            to="/health"
            class="text-sm text-gray-600 hover:text-emerald-700 transition-colors"
          >
            My Health
          </router-link>
          <router-link
            to="/library"
            class="text-sm text-gray-600 hover:text-emerald-700 transition-colors"
          >
            Library
          </router-link>
          <router-link
            to="/scribe"
            class="text-sm text-gray-600 hover:text-emerald-700 transition-colors flex items-center gap-1.5"
          >
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
            </span>
            Record Visit
          </router-link>
          <div v-if="auth.user" class="relative pl-3 border-l border-gray-200">
            <button
              class="flex items-center gap-2 hover:opacity-80 transition-opacity"
              @click="dropdownOpen = !dropdownOpen"
            >
              <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
                {{ initials }}
              </div>
              <span class="text-sm text-gray-700 font-medium">{{ auth.user.name }}</span>
              <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': dropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="dropdownOpen"
              class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
            >
              <router-link
                to="/settings"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
                @click="dropdownOpen = false"
              >
                Settings
              </router-link>
              <button
                class="w-full text-left px-4 py-2 text-sm text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                @click="handleLogout"
              >
                Log Out
              </button>
            </div>
          </div>
        </nav>

        <!-- Mobile hamburger -->
        <button
          class="md:hidden p-2 text-gray-600 hover:text-emerald-700 transition-colors"
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

      <!-- Mobile menu -->
      <div v-if="mobileOpen" class="md:hidden border-t border-emerald-100 bg-white">
        <div class="px-4 py-3 space-y-1">
          <div v-if="auth.user" class="flex items-center gap-3 px-3 py-2 mb-1 border-b border-gray-100">
            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">
              {{ initials }}
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ auth.user.name }}</p>
              <p class="text-xs text-emerald-600 font-medium">Patient Panel</p>
            </div>
          </div>
          <router-link
            to="/profile"
            class="block px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
            @click="mobileOpen = false"
          >
            Profile
          </router-link>
          <router-link
            to="/health"
            class="block px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
            @click="mobileOpen = false"
          >
            My Health
          </router-link>
          <router-link
            to="/library"
            class="block px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
            @click="mobileOpen = false"
          >
            Library
          </router-link>
          <router-link
            to="/scribe"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
            @click="mobileOpen = false"
          >
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
            </span>
            Record Visit
          </router-link>
          <router-link
            to="/settings"
            class="block px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
            @click="mobileOpen = false"
          >
            Settings
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

    <!-- Main content -->
    <main :class="[wide ? 'max-w-7xl' : 'max-w-4xl', 'mx-auto px-4 py-6']">
      <slot />
    </main>

    <!-- Disclaimer footer -->
    <footer :class="[wide ? 'max-w-7xl' : 'max-w-4xl', 'mx-auto px-4 py-4 text-center']">
      <p class="text-xs text-gray-400 leading-relaxed">
        All clinical scenarios, patient data, and medical records displayed in this application are entirely fictional,
        created for demonstration purposes only, and do not depict any real person or actual medical encounter.
      </p>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

defineProps({
    wide: { type: Boolean, default: false },
});

const auth = useAuthStore();
const router = useRouter();
const mobileOpen = ref(false);
const dropdownOpen = ref(false);

function closeDropdown(e) {
    if (dropdownOpen.value && !e.target.closest('.relative')) {
        dropdownOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdown);
});

const isDemoUser = computed(() => {
    return auth.user?.email?.endsWith('@demo.postvisit.ai') ?? false;
});

const initials = computed(() => {
    if (!auth.user?.name) return '?';
    return auth.user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
});

async function handleLogout() {
    mobileOpen.value = false;
    dropdownOpen.value = false;
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>
