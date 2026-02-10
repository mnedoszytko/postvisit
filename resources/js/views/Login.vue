<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <router-link to="/" class="text-2xl font-bold text-emerald-700">PostVisit.ai</router-link>
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

      <div class="mt-6 space-y-2">
        <p class="text-center text-xs text-gray-400 uppercase tracking-wide">Demo Access</p>
        <div class="flex gap-2">
          <button
            :disabled="loading"
            class="flex-1 py-2.5 bg-white text-emerald-700 border border-emerald-300 rounded-lg font-medium hover:bg-emerald-50 transition-colors disabled:opacity-50 text-sm"
            @click="demoLogin('patient')"
          >
            Sign in as Patient
          </button>
          <button
            :disabled="loading"
            class="flex-1 py-2.5 bg-white text-indigo-700 border border-indigo-300 rounded-lg font-medium hover:bg-indigo-50 transition-colors disabled:opacity-50 text-sm"
            @click="demoLogin('doctor')"
          >
            Sign in as Doctor
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
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
