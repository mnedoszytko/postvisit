<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <router-link to="/" class="inline-block"><img src="/images/logo-full.png" alt="PostVisit.ai" class="h-8 mx-auto" /></router-link>
      </div>

      <!-- Demo Access — prominent, above login form -->
      <div
        :class="[
          'login-demo-section mb-6 space-y-3 rounded-2xl border-2 border-emerald-400 bg-emerald-50/50 p-5 shadow-lg transition-all duration-700 ease-out',
          demoVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
        ]"
      >
        <p class="text-center text-sm font-semibold text-emerald-800 uppercase tracking-wide">Try the Demo</p>
        <p class="text-center text-xs text-emerald-600">No account needed — explore as patient or doctor</p>
        <div class="flex gap-2">
          <button
            :disabled="loading"
            class="flex-1 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 text-sm"
            @click="router.push('/demo/scenarios')"
          >
            Sign in as Patient
          </button>
          <button
            :disabled="loading"
            class="flex-1 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 text-sm"
            @click="demoLogin('doctor')"
          >
            Sign in as Doctor
          </button>
        </div>
      </div>

      <div class="relative flex items-center mb-6">
        <div class="flex-1 border-t border-gray-300" />
        <span class="mx-3 text-xs text-gray-400 uppercase">or sign in</span>
        <div class="flex-1 border-t border-gray-300" />
      </div>

      <form class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4" @submit.prevent="handleLogin">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            placeholder="Your password"
          />
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-4">
        Don't have an account?
        <router-link to="/register" class="text-emerald-600 font-medium hover:text-emerald-700">Sign up</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useApi } from '@/composables/useApi';
import { useRouter, useRoute } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function handleLogin() {
    loading.value = true;
    error.value = '';
    try {
        await auth.login(email.value, password.value);
        const redirect = route.query.redirect || (auth.isDoctor ? '/doctor' : '/profile');
        router.push(redirect);
    } catch (err) {
        error.value = err.response?.data?.error?.message || 'Invalid credentials. Please try again.';
    } finally {
        loading.value = false;
    }
}

const demoVisible = ref(false);
onMounted(() => {
    // Trigger entrance animation after mount
    requestAnimationFrame(() => { demoVisible.value = true; });
});

async function demoLogin(role) {
    loading.value = true;
    error.value = '';
    try {
        const api = useApi();
        const { data } = await api.post('/demo/start', { role });
        auth.user = data.data.user;
        auth.token = data.data.token;
        router.push(role === 'doctor' ? '/doctor' : '/profile');
    } catch (err) {
        error.value = err.response?.data?.error?.message || 'Demo data not seeded. Run: php artisan db:seed --class=DemoSeeder';
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.login-demo-section {
    animation: demo-glow 2.5s ease-in-out infinite alternate;
}

@keyframes demo-glow {
    from {
        box-shadow: 0 0 8px -2px rgb(16 185 129 / 0.3);
    }
    to {
        box-shadow: 0 0 20px -2px rgb(16 185 129 / 0.5);
    }
}
</style>
