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
        <div class="flex items-center gap-2">
          <button
            class="px-3 py-1 bg-amber-900/10 hover:bg-amber-900/20 rounded-lg text-xs font-semibold transition-colors"
            :disabled="switchingRole"
            @click="switchToDoctor"
          >
            {{ switchingRole ? 'Switching...' : 'Doctor Panel' }}
          </button>
          <router-link
            to="/demo/scenarios"
            class="px-3 py-1 bg-amber-900/10 hover:bg-amber-900/20 rounded-lg text-xs font-semibold transition-colors"
          >
            Switch Scenario
          </router-link>
        </div>
      </div>
    </div>

    <!-- Header -->
    <header :class="['bg-white border-b border-emerald-200 sticky z-40', isDemoUser ? 'top-10' : 'top-0']">
      <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <router-link to="/profile" class="flex items-center">
            <img src="/images/logo-full.png" alt="PostVisit.ai" class="h-7" />
          </router-link>
          <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
            Patient Panel
          </span>
        </div>

        <!-- Desktop nav -->
        <nav class="hidden md:flex items-center gap-4">
          <router-link
            to="/profile"
            :class="['text-sm transition-colors', isActive('/profile') ? 'text-emerald-700 font-semibold' : 'text-gray-600 hover:text-emerald-700']"
          >
            Profile
          </router-link>
          <router-link
            to="/health"
            :class="['text-sm transition-colors', isActive('/health') ? 'text-emerald-700 font-semibold' : 'text-gray-600 hover:text-emerald-700']"
          >
            My Health
          </router-link>
          <router-link
            to="/library"
            :class="['text-sm transition-colors', isActive('/library') ? 'text-emerald-700 font-semibold' : 'text-gray-600 hover:text-emerald-700']"
          >
            Reference
          </router-link>
          <router-link
            to="/scribe"
            :class="['text-sm transition-colors flex items-center gap-1.5', isActive('/scribe') ? 'text-emerald-700 font-semibold' : 'text-gray-600 hover:text-emerald-700']"
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
              <img
                v-if="auth.user.photo_url"
                :src="auth.user.photo_url"
                :alt="auth.user.name"
                class="w-7 h-7 rounded-full object-cover"
              />
              <div v-else class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
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
            <img
              v-if="auth.user.photo_url"
              :src="auth.user.photo_url"
              :alt="auth.user.name"
              class="w-8 h-8 rounded-full object-cover"
            />
            <div v-else class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">
              {{ initials }}
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ auth.user.name }}</p>
              <p class="text-xs text-emerald-600 font-medium">Patient Panel</p>
            </div>
          </div>
          <router-link
            to="/profile"
            :class="['block px-3 py-2 rounded-lg text-sm transition-colors', isActive('/profile') ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700 hover:bg-emerald-50 hover:text-emerald-700']"
            @click="mobileOpen = false"
          >
            Profile
          </router-link>
          <router-link
            to="/health"
            :class="['block px-3 py-2 rounded-lg text-sm transition-colors', isActive('/health') ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700 hover:bg-emerald-50 hover:text-emerald-700']"
            @click="mobileOpen = false"
          >
            My Health
          </router-link>
          <router-link
            to="/library"
            :class="['block px-3 py-2 rounded-lg text-sm transition-colors', isActive('/library') ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700 hover:bg-emerald-50 hover:text-emerald-700']"
            @click="mobileOpen = false"
          >
            Reference
          </router-link>
          <router-link
            to="/scribe"
            :class="['flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors', isActive('/scribe') ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700 hover:bg-emerald-50 hover:text-emerald-700']"
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

    <!-- Main content — add right margin on desktop when chat is open -->
    <main
      :class="[wide ? 'max-w-7xl' : 'max-w-4xl', 'mx-auto px-4 py-6']"
      :style="chatMarginStyle"
    >
      <slot />
    </main>

    <!-- Disclaimer footer -->
    <footer
      :class="[wide ? 'max-w-7xl' : 'max-w-4xl', 'mx-auto px-4 py-4 text-center']"
      :style="chatMarginStyle"
    >
      <p class="text-xs text-gray-400 leading-relaxed">
        All clinical scenarios, patient data, and medical records displayed in this application are entirely fictional,
        created for demonstration purposes only, and do not depict any real person or actual medical encounter.
      </p>
      <p class="text-[10px] text-gray-300 mt-2">build {{ gitHash }}</p>
    </footer>

    <!-- Desktop: fixed chat panel (right side, below headers) -->
    <div
      v-if="showGlobalChat && latestVisitId && chatOpen"
      class="hidden lg:flex fixed right-0 border-l border-gray-200 bg-white z-30 flex-col"
      :style="{ top: chatTopOffset + 'px', height: 'calc(100vh - ' + chatTopOffset + 'px)', width: chatWidth + 'px' }"
    >
      <!-- Drag handle to resize -->
      <div
        class="absolute left-0 top-0 bottom-0 w-1.5 cursor-col-resize hover:bg-emerald-400/40 active:bg-emerald-400/60 transition-colors z-10"
        @mousedown.prevent="startResize"
      />
      <ChatPanel
        :visit-id="latestVisitId"
        :initial-context="chatContext"
        :context-key="chatContextKey"
        :embedded="true"
        :maximized="chatMaximized"
        @close="chatOpen = false"
        @toggle-maximize="toggleMaximize"
      />
    </div>

    <!-- Global AI Chat — mobile/tablet overlay + floating button -->
    <template v-if="showGlobalChat && latestVisitId">
      <!-- Floating chat button (shown when chat is closed, or always on mobile) -->
      <button
        v-if="!chatOpen"
        class="fixed bottom-6 right-6 z-40 w-14 h-14 bg-emerald-50 hover:bg-emerald-100 rounded-full shadow-lg hover:shadow-xl transition-all flex items-center justify-center group"
        title="Ask PostVisit AI"
        @click="chatOpen = true"
      >
        <img src="/images/logo-icon.png" alt="PostVisit" class="h-7 w-auto group-hover:scale-110 transition-transform" />
        <span class="absolute -top-1 -right-1 w-3 h-3 bg-amber-400 rounded-full animate-pulse"></span>
      </button>

      <!-- Mobile/tablet: chat panel overlay -->
      <Transition name="chat-slide">
        <div v-if="chatOpen" class="lg:hidden fixed inset-y-0 right-0 z-50 w-full sm:w-96 flex flex-col bg-white shadow-2xl border-l border-gray-200">
          <ChatPanel
            :visit-id="latestVisitId"
            :initial-context="chatContext"
            :context-key="chatContextKey"
            :embedded="true"
            @close="chatOpen = false"
          />
        </div>
      </Transition>

      <!-- Mobile backdrop -->
      <Transition name="fade">
        <div
          v-if="chatOpen"
          class="lg:hidden fixed inset-0 z-40 bg-black/30"
          @click="chatOpen = false"
        ></div>
      </Transition>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, provide, onMounted, onBeforeUnmount, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useVisitStore } from '@/stores/visit';
import { useApi } from '@/composables/useApi';
import { useRouter, useRoute } from 'vue-router';
import { useChatBus } from '@/composables/useChatBus';
import ChatPanel from '@/components/ChatPanel.vue';

defineProps({
    wide: { type: Boolean, default: false },
});

const gitHash = __GIT_HASH__;

const auth = useAuthStore();
const visitStore = useVisitStore();
const api = useApi();
const router = useRouter();
const route = useRoute();
const mobileOpen = ref(false);
const dropdownOpen = ref(false);
const switchingRole = ref(false);
const chatOpen = ref(window.innerWidth >= 1024);
const chatContext = ref('');
const latestVisitId = ref(null);
const chatWidth = ref(384); // default w-96
const chatMaximized = ref(false);
const chatWidthBeforeMax = ref(384);
const isResizing = ref(false);
const chatContextKey = ref(0);

function toggleMaximize() {
    if (chatMaximized.value) {
        chatWidth.value = chatWidthBeforeMax.value;
        chatMaximized.value = false;
    } else {
        chatWidthBeforeMax.value = chatWidth.value;
        chatWidth.value = Math.max(600, Math.round(window.innerWidth / 2));
        chatMaximized.value = true;
    }
}

function openGlobalChat(context = '') {
    chatContext.value = context;
    chatContextKey.value++;
    chatOpen.value = true;
}
provide('openGlobalChat', openGlobalChat);
provide('setChatContext', (ctx) => { chatContext.value = ctx; });

// Listen for chat requests from the global bus (used by views where inject can't reach)
const { chatContextRequest } = useChatBus();
watch(chatContextRequest, (req) => {
    if (req.timestamp > 0) {
        openGlobalChat(req.context);
    }
});

// Hide global chat on pages that have their own chat or where it doesn't make sense
const hideChatRoutes = ['visit-view', 'meds-detail', 'companion-scribe', 'processing', 'feedback'];
const showGlobalChat = computed(() => {
    return !hideChatRoutes.includes(route.name);
});

// Map routes to default chat contexts
const routeContextMap = {
    'health-dashboard': 'health',
    'medical-library': 'reference',
    'patient-profile': '',
    'settings': '',
};

// Set initial context based on current route
function updateContextForRoute(routeName) {
    if (!routeName) return;
    const defaultCtx = routeContextMap[routeName];
    if (defaultCtx !== undefined) {
        chatContext.value = defaultCtx;
    }
}

// Set context on initial load
updateContextForRoute(route.name);

// Close chat when navigating to a page with its own chat, and auto-update context per route
watch(() => route.name, (newRoute) => {
    if (hideChatRoutes.includes(newRoute)) {
        chatOpen.value = false;
        return;
    }
    // Auto-set default context based on current route
    updateContextForRoute(newRoute);
});

function closeDropdown(e) {
    if (dropdownOpen.value && !e.target.closest('.relative')) {
        dropdownOpen.value = false;
    }
}

onMounted(async () => {
    document.addEventListener('click', closeDropdown);

    // Fetch latest visit ID for global chat context
    if (auth.user?.patient_id) {
        try {
            if (!visitStore.visits.length) {
                await visitStore.fetchVisits(auth.user.patient_id);
            }
            if (visitStore.visits.length > 0) {
                latestVisitId.value = visitStore.visits[0].id;
            }
        } catch {
            // No visits — chat button won't show
        }
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdown);
});

const isDemoUser = computed(() => {
    return auth.user?.email?.endsWith('@demo.postvisit.ai') ?? false;
});

// Chat panel top offset: header (64px) + demo banner (40px) if present
const chatTopOffset = computed(() => {
    return isDemoUser.value ? 104 : 64;
});

// Dynamic margin for main content when chat is open (desktop only)
const chatMarginStyle = computed(() => {
    if (showGlobalChat.value && latestVisitId.value && chatOpen.value && window.innerWidth >= 1024) {
        return { marginRight: chatWidth.value + 'px' };
    }
    return {};
});

// Resize drag logic
function startResize(e) {
    isResizing.value = true;
    const startX = e.clientX;
    const startWidth = chatWidth.value;
    function onMove(ev) {
        const maxWidth = Math.round(window.innerWidth * 0.7);
        const newWidth = Math.min(maxWidth, Math.max(320, startWidth + (startX - ev.clientX)));
        chatMaximized.value = false;
        chatWidth.value = newWidth;
    }
    function onUp() {
        isResizing.value = false;
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    }
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
}

const initials = computed(() => {
    if (!auth.user?.name) return '?';
    return auth.user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
});

async function switchToDoctor() {
    switchingRole.value = true;
    try {
        const { data } = await api.post('/demo/switch-to-doctor');
        auth.user = data.data.user;
        auth.token = data.data.token;
        router.push('/doctor');
    } catch {
        switchingRole.value = false;
    }
}

function isActive(path) {
    return route.path === path || route.path.startsWith(path + '/');
}

async function handleLogout() {
    mobileOpen.value = false;
    dropdownOpen.value = false;
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>
