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
        <div class="relative w-full max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[90vh] flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between p-5 border-b border-gray-100 shrink-0">
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

          <!-- Main content (thread + form) -->
          <div v-else class="flex flex-col min-h-0 flex-1 overflow-hidden">
            <!-- Conversation thread -->
            <div
              v-if="thread.length > 0"
              ref="threadContainer"
              class="flex-1 overflow-y-auto px-5 pt-4 pb-2 space-y-3 min-h-0 max-h-64"
            >
              <div
                v-for="msg in thread"
                :key="msg.id"
                class="flex"
                :class="msg.type === 'doctor_reply' ? 'justify-start' : 'justify-end'"
              >
                <div
                  class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm"
                  :class="msg.type === 'doctor_reply'
                    ? 'bg-blue-50 text-blue-900 rounded-bl-md'
                    : 'bg-emerald-50 text-emerald-900 rounded-br-md'"
                >
                  <p class="whitespace-pre-wrap">{{ msg.body }}</p>
                  <p class="text-[10px] mt-1 opacity-50">{{ formatTime(msg.created_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Loading thread -->
            <div v-if="loadingThread" class="px-5 py-3 text-center text-sm text-gray-400">
              Loading messages...
            </div>

            <!-- Form -->
            <div class="p-5 space-y-4 shrink-0 border-t border-gray-50">
              <!-- Doctor info -->
              <div v-if="visit?.practitioner" class="flex items-center gap-3">
                <img
                  v-if="visit.practitioner.photo_url"
                  :src="visit.practitioner.photo_url"
                  :alt="doctorName"
                  class="w-10 h-10 rounded-full object-cover shrink-0"
                />
                <div v-else class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <div>
                  <p class="font-semibold text-gray-900 text-sm">{{ doctorName }}</p>
                  <p v-if="visit.practitioner.primary_specialty" class="text-xs text-blue-600 capitalize">
                    {{ visit.practitioner.primary_specialty }}
                  </p>
                </div>
              </div>

              <!-- Visit context toggle -->
              <label class="flex items-center gap-2 cursor-pointer bg-gray-50 rounded-xl px-3 py-2">
                <input v-model="includeContext" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                <span class="text-sm text-gray-700">Include visit context</span>
              </label>

              <!-- Category pills -->
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="cat in categories"
                  :key="cat"
                  type="button"
                  class="px-3 py-1.5 text-xs rounded-full border transition-colors"
                  :class="category === cat
                    ? 'bg-emerald-600 text-white border-emerald-600'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-300 hover:text-emerald-700'"
                  @click="category = cat"
                >
                  {{ cat }}
                </button>
              </div>

              <!-- Message textarea + send -->
              <div class="flex gap-2 items-end">
                <textarea
                  v-model="message"
                  rows="2"
                  placeholder="Ask a question or share an update..."
                  class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none"
                />
                <button
                  :disabled="!message.trim() || sending"
                  class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                  @click="send"
                >
                  <svg v-if="sending" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
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
const thread = ref([]);
const loadingThread = ref(false);
const threadContainer = ref(null);

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

function formatTime(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

async function fetchThread() {
    if (!props.visit?.id) return;
    loadingThread.value = true;
    try {
        const res = await api.get(`/visits/${props.visit.id}/messages`);
        thread.value = res.data.data || [];
        await nextTick();
        scrollToBottom();
    } catch {
        // Handled by interceptor
    } finally {
        loadingThread.value = false;
    }
}

function scrollToBottom() {
    if (threadContainer.value) {
        threadContainer.value.scrollTop = threadContainer.value.scrollHeight;
    }
}

// Fetch thread when modal opens
watch(() => props.modelValue, (open) => {
    if (open && props.visit?.id) {
        fetchThread();
    }
});

function close() {
    emit('update:modelValue', false);
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

        message.value = '';
        // Refresh thread to show the new message
        await fetchThread();
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
