<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
        @click.self="close"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40" @click="close" />

        <!-- Modal panel -->
        <div class="relative w-full max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Contact Doctor</h2>
            <button
              class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
              @click="close"
            >
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <!-- Success state -->
          <div v-if="sent" class="p-8 text-center">
            <div class="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <p class="text-lg font-semibold text-gray-900 mb-1">Message Sent</p>
            <p class="text-sm text-gray-500">Your doctor will be notified.</p>
          </div>

          <!-- Form -->
          <div v-else class="p-5 space-y-5">
            <!-- Doctor info -->
            <div v-if="visit?.practitioner" class="flex items-center gap-3">
              <img
                v-if="visit.practitioner.photo_url"
                :src="visit.practitioner.photo_url"
                :alt="doctorName"
                class="w-12 h-12 rounded-full object-cover shrink-0"
              />
              <div v-else class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
              </div>
              <div>
                <p class="font-semibold text-gray-900">{{ doctorName }}</p>
                <p v-if="visit.practitioner.primary_specialty" class="text-sm text-blue-600 capitalize">
                  {{ visit.practitioner.primary_specialty }}
                </p>
              </div>
            </div>

            <!-- Visit context -->
            <div class="bg-gray-50 rounded-xl p-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="includeContext" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                <span class="text-sm font-medium text-gray-700">Include visit context</span>
              </label>
              <div v-if="includeContext" class="mt-3 text-sm text-gray-600 space-y-1">
                <p v-if="visit?.started_at">
                  <span class="font-medium text-gray-700">Date:</span> {{ formatDate(visit.started_at) }}
                </p>
                <p v-if="visit?.reason_for_visit">
                  <span class="font-medium text-gray-700">Reason:</span> {{ visit.reason_for_visit }}
                </p>
                <p v-if="visit?.visit_type">
                  <span class="font-medium text-gray-700">Type:</span> {{ formatVisitType(visit.visit_type) }}
                </p>
              </div>
            </div>

            <!-- Category pills -->
            <div>
              <p class="text-sm font-medium text-gray-700 mb-2">Category</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="cat in categories"
                  :key="cat"
                  type="button"
                  class="px-3 py-1.5 text-sm rounded-full border transition-colors"
                  :class="category === cat
                    ? 'bg-emerald-600 text-white border-emerald-600'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-300 hover:text-emerald-700'"
                  @click="category = cat"
                >
                  {{ cat }}
                </button>
              </div>
            </div>

            <!-- Message textarea -->
            <div>
              <textarea
                v-model="message"
                rows="4"
                placeholder="Ask a question or share an update..."
                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none"
              />
            </div>

            <!-- Send button -->
            <button
              :disabled="!message.trim() || sending"
              class="w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              @click="send"
            >
              <svg v-if="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              {{ sending ? 'Sending...' : 'Send Message' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useApi } from '@/composables/useApi';

const props = defineProps({
    visit: { type: Object, default: null },
    modelValue: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const api = useApi();

const categories = ['Question', 'Side Effect', 'Update', 'Urgent'];
const category = ref('Question');
const message = ref('');
const includeContext = ref(true);
const sending = ref(false);
const sent = ref(false);

const doctorName = computed(() => {
    const p = props.visit?.practitioner;
    if (!p) return 'Your Doctor';
    return `Dr. ${p.first_name} ${p.last_name}`;
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatVisitType(type) {
    if (!type) return '';
    return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function close() {
    emit('update:modelValue', false);
    // Reset after close animation
    setTimeout(() => {
        if (!props.modelValue) {
            message.value = '';
            category.value = 'Question';
            sent.value = false;
        }
    }, 300);
}

async function send() {
    if (!message.value.trim() || !props.visit?.id) return;

    sending.value = true;
    try {
        let body = message.value.trim();
        if (includeContext.value) {
            const parts = [];
            if (props.visit.started_at) parts.push(`Visit date: ${formatDate(props.visit.started_at)}`);
            if (props.visit.reason_for_visit) parts.push(`Reason: ${props.visit.reason_for_visit}`);
            if (props.visit.visit_type) parts.push(`Type: ${formatVisitType(props.visit.visit_type)}`);
            if (parts.length) {
                body += `\n\n--- Visit Context ---\n${parts.join('\n')}`;
            }
        }

        await api.post(`/visits/${props.visit.id}/messages`, {
            title: category.value,
            body,
        });

        sent.value = true;
        setTimeout(() => close(), 1500);
    } catch {
        // Handled by API interceptor
    } finally {
        sending.value = false;
    }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
    transition: transform 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div:last-child {
    transform: translateY(1rem);
}
.modal-leave-to > div:last-child {
    transform: translateY(1rem);
}
</style>
