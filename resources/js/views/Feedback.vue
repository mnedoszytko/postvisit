<template>
  <PatientLayout>
    <div class="max-w-lg mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Contact Your Doctor</h1>
        <router-link
          :to="`/visits/${route.params.id}`"
          class="text-sm text-indigo-600 hover:text-indigo-700"
        >
          &larr; Back to Visit
        </router-link>
      </div>

      <!-- Doctor card -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 flex items-center gap-4">
        <div class="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center text-lg font-bold text-emerald-700">
          Dr
        </div>
        <div>
          <p class="font-semibold text-gray-900">Your Doctor</p>
          <p class="text-sm text-gray-500">Cardiology</p>
        </div>
      </div>

      <!-- Message thread -->
      <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
        <div v-if="messages.length === 0" class="p-6 text-center text-gray-400">
          No messages yet. Send your first message to your doctor.
        </div>
        <div
          v-for="msg in messages"
          :key="msg.id"
          class="p-4"
          :class="msg.sender === 'patient' ? 'bg-emerald-50' : ''"
        >
          <p class="text-xs text-gray-400 mb-1">{{ msg.sender === 'patient' ? 'You' : 'Doctor' }}</p>
          <p class="text-gray-800">{{ msg.content }}</p>
        </div>
      </div>

      <!-- Message input -->
      <form class="flex gap-2" @submit.prevent="sendMessage">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Write a message..."
          class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
        />
        <button
          type="submit"
          :disabled="!newMessage.trim()"
          class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
        >
          Send
        </button>
      </form>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
import PatientLayout from '@/layouts/PatientLayout.vue';

const route = useRoute();
const api = useApi();
const messages = ref([]);
const newMessage = ref('');

async function sendMessage() {
    if (!newMessage.value.trim()) return;
    try {
        const { data } = await api.post(`/visits/${route.params.id}/messages`, {
            content: newMessage.value,
        });
        messages.value.push(data.data);
        newMessage.value = '';
    } catch (err) {
        console.error('Send message failed:', err);
    }
}

onMounted(async () => {
    try {
        const { data } = await api.get(`/visits/${route.params.id}/messages`);
        messages.value = data.data;
    } catch (err) {
        console.error('Fetch messages failed:', err);
    }
});
</script>
