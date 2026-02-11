<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <router-link to="/" class="text-2xl font-bold text-emerald-700">PostVisit.ai</router-link>
        <p class="text-gray-500 text-sm mt-1">Create your account</p>
      </div>

      <form class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4" @submit.prevent="handleRegister">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            placeholder="Alex Johnson"
          />
          <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name }}</p>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            placeholder="you@example.com"
          />
          <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email }}</p>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            minlength="8"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            placeholder="Min. 8 characters"
          />
          <p v-if="errors.password" class="text-xs text-red-600 mt-1">{{ errors.password }}</p>
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
          <input
            id="password_confirmation"
            v-model="form.passwordConfirmation"
            type="password"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            placeholder="Repeat your password"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">I am a</label>
          <div class="flex gap-2">
            <button
              type="button"
              :class="[
                'flex-1 py-2 rounded-lg border font-medium text-sm transition-colors',
                form.role === 'patient'
                  ? 'bg-emerald-600 text-white border-emerald-600'
                  : 'bg-white text-gray-600 border-gray-300 hover:border-emerald-400'
              ]"
              @click="form.role = 'patient'"
            >
              Patient
            </button>
            <button
              type="button"
              :class="[
                'flex-1 py-2 rounded-lg border font-medium text-sm transition-colors',
                form.role === 'doctor'
                  ? 'bg-indigo-600 text-white border-indigo-600'
                  : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400'
              ]"
              @click="form.role = 'doctor'"
            >
              Doctor
            </button>
          </div>
          <p v-if="errors.role" class="text-xs text-red-600 mt-1">{{ errors.role }}</p>
        </div>

        <p v-if="generalError" class="text-sm text-red-600">{{ generalError }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Creating account...' : 'Create Account' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-4">
        Already have an account?
        <router-link to="/login" class="text-emerald-600 font-medium hover:text-emerald-700">Sign in</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
    role: 'patient',
});

const errors = reactive({
    name: '',
    email: '',
    password: '',
    role: '',
});

const generalError = ref('');
const loading = ref(false);

function clearErrors() {
    errors.name = '';
    errors.email = '';
    errors.password = '';
    errors.role = '';
    generalError.value = '';
}

async function handleRegister() {
    clearErrors();
    loading.value = true;

    try {
        await auth.register(
            form.name,
            form.email,
            form.password,
            form.passwordConfirmation,
            form.role,
        );
        router.push(auth.isDoctor ? '/doctor' : '/profile');
    } catch (err) {
        const serverErrors = err.response?.data?.errors;
        if (serverErrors) {
            if (serverErrors.name) errors.name = serverErrors.name[0];
            if (serverErrors.email) errors.email = serverErrors.email[0];
            if (serverErrors.password) errors.password = serverErrors.password[0];
            if (serverErrors.role) errors.role = serverErrors.role[0];
        } else {
            generalError.value = err.response?.data?.message || 'Registration failed. Please try again.';
        }
    } finally {
        loading.value = false;
    }
}
</script>
