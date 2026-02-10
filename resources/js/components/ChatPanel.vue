<template>
  <div class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white border-l border-gray-200 shadow-xl z-50 flex flex-col">
    <!-- Header -->
    <div class="h-16 border-b border-gray-200 flex items-center justify-between px-4 shrink-0">
      <h3 class="font-semibold text-gray-800">AI Assistant</h3>
      <button
        class="text-gray-400 hover:text-gray-600 transition-colors text-xl"
        @click="$emit('close')"
      >
        &times;
      </button>
    </div>

    <!-- Messages -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
      <div
        v-for="(msg, i) in chatStore.messages"
        :key="i"
        :class="msg.role === 'user' ? 'ml-8' : 'mr-8'"
      >
        <div
          :class="[
            'rounded-2xl px-4 py-3 text-sm',
            msg.role === 'user'
              ? 'bg-emerald-600 text-white'
              : 'bg-gray-100 text-gray-800'
          ]"
        >
          <StreamingMessage v-if="msg.streaming" :text="msg.content" />
          <div v-else-if="msg.role === 'assistant'" class="prose prose-sm max-w-none" v-html="renderMarkdown(msg.content)" />
          <p v-else>{{ msg.content }}</p>
        </div>
      </div>

      <div v-if="chatStore.loading" class="mr-8">
        <div class="bg-gray-100 rounded-2xl px-4 py-3 text-sm text-gray-400 animate-pulse">
          Thinking...
        </div>
      </div>
    </div>

    <!-- Input -->
    <form class="border-t border-gray-200 p-4 flex gap-2 shrink-0" @submit.prevent="send">
      <input
        v-model="message"
        type="text"
        placeholder="Ask about your visit..."
        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
      />
      <button
        type="submit"
        :disabled="!message.trim() || chatStore.loading"
        class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
      >
        Send
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { useChatStore } from '@/stores/chat';
import { marked } from 'marked';
import StreamingMessage from '@/components/StreamingMessage.vue';

marked.setOptions({ breaks: true, gfm: true });
function renderMarkdown(text) {
    return marked.parse(text || '');
}

const props = defineProps({
    visitId: { type: String, required: true },
    initialContext: { type: String, default: '' },
});

defineEmits(['close']);

const chatStore = useChatStore();
const message = ref('');
const messagesContainer = ref(null);

async function send() {
    if (!message.value.trim()) return;
    const text = message.value;
    message.value = '';
    await chatStore.sendMessage(props.visitId, text);
    scrollToBottom();
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

watch(() => chatStore.messages.length, scrollToBottom);

onMounted(() => {
    chatStore.clearMessages();
    if (props.initialContext) {
        message.value = `Explain: ${props.initialContext}`;
    }
});
</script>
