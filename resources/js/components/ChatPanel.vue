<template>
  <div
    :class="[
      'fixed inset-y-0 right-0 w-full sm:w-96 bg-white border-l shadow-xl z-50 flex flex-col transition-all duration-500',
      highlight ? 'border-emerald-400 shadow-emerald-200/50 ring-2 ring-emerald-300' : 'border-gray-200'
    ]"
  >
    <!-- Header -->
    <div class="h-16 border-b border-gray-200 flex items-center justify-between px-4 shrink-0">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 text-sm">PostVisit AI</h3>
          <p v-if="chatStore.loading" class="text-[10px] text-emerald-600 font-medium">Reviewing your visit...</p>
          <p v-else class="text-[10px] text-gray-400">Ask anything about your visit</p>
        </div>
      </div>
      <button
        class="text-gray-400 hover:text-gray-600 transition-colors text-xl leading-none"
        @click="$emit('close')"
      >
        &times;
      </button>
    </div>

    <!-- Welcome message when empty -->
    <div v-if="!chatStore.messages.length && !chatStore.loading" class="flex-1 flex flex-col items-center justify-center p-6 text-center">
      <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
        </svg>
      </div>
      <h4 class="font-semibold text-gray-800 mb-1">Your visit assistant</h4>
      <p v-if="initialContext" class="text-xs text-emerald-600 font-medium mb-2 px-3 py-1 bg-emerald-50 rounded-full inline-block">
        Context: {{ initialContext }}
      </p>
      <p class="text-sm text-gray-500 mb-6 max-w-[240px]">
        {{ initialContext ? `Ask me anything about your ${initialContext.toLowerCase()}.` : 'I have the full context of your visit. Ask me anything about your diagnosis, medications, or next steps.' }}
      </p>
      <div class="space-y-2 w-full">
        <button
          v-for="q in suggestedQuestions"
          :key="q"
          class="w-full text-left text-sm px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all"
          @click="sendQuestion(q)"
        >
          {{ q }}
        </button>
      </div>
    </div>

    <!-- Messages -->
    <div v-else ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
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
          <!-- Streaming with thinking active -->
          <ThinkingIndicator
            v-if="msg.streaming && !msg.content"
            :query="lastUserMessage"
            :thinking-active="msg.thinkingPhase"
          />
          <StreamingMessage v-else-if="msg.streaming" :text="stripSources(msg.content)" />
          <div v-else-if="msg.role === 'assistant'" class="prose prose-sm max-w-none" v-html="renderMarkdown(stripSources(msg.content))" />
          <p v-else>{{ msg.content }}</p>
        </div>
        <!-- Source chips for completed assistant messages -->
        <SourceChips
          v-if="msg.role === 'assistant' && !msg.streaming && parseSources(msg.content).length"
          :sources="parseSources(msg.content)"
          class="mt-1.5"
        />
        <!-- Collapsible AI Reasoning for completed messages with thinking -->
        <div
          v-if="msg.role === 'assistant' && !msg.streaming && msg.thinking"
          class="mt-2"
        >
          <button
            class="flex items-center gap-1 text-xs text-amber-700 hover:text-amber-900 transition-colors"
            @click="toggleThinking(i)"
          >
            <svg
              class="w-3 h-3 transition-transform"
              :class="{ 'rotate-90': expandedThinking[i] }"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
            AI Reasoning
          </button>
          <div
            v-if="expandedThinking[i]"
            class="mt-1.5 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 max-h-48 overflow-y-auto"
          >
            <pre class="text-[11px] text-amber-900 font-mono whitespace-pre-wrap break-words leading-relaxed">{{ msg.thinking }}</pre>
          </div>
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
        :disabled="chatStore.loading"
      />
      <button
        type="submit"
        :disabled="!message.trim() || chatStore.loading"
        class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="chatStore.loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span v-else>Send</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, reactive } from 'vue';
import { useChatStore } from '@/stores/chat';
import { marked } from 'marked';
import StreamingMessage from '@/components/StreamingMessage.vue';
import ThinkingIndicator from '@/components/ThinkingIndicator.vue';
import SourceChips from '@/components/SourceChips.vue';

marked.setOptions({ breaks: true, gfm: true });
function renderMarkdown(text) {
    return marked.parse(text || '');
}

function stripSources(text) {
    if (!text) return '';
    return text.replace(/\[sources\][\s\S]*?\[\/sources\]/g, '').trim();
}

function parseSources(text) {
    if (!text) return [];
    const match = text.match(/\[sources\]([\s\S]*?)\[\/sources\]/);
    if (!match) return [];
    return match[1]
        .split('\n')
        .map(line => line.trim().replace(/^-\s*/, ''))
        .filter(line => line.includes('|'))
        .map(line => {
            const [label, key] = line.split('|').map(s => s.trim());
            return { label, key };
        });
}

const props = defineProps({
    visitId: { type: String, required: true },
    initialContext: { type: String, default: '' },
    highlight: { type: Boolean, default: false },
});

defineEmits(['close']);

const chatStore = useChatStore();
const message = ref('');
const messagesContainer = ref(null);
const expandedThinking = reactive({});

const lastUserMessage = computed(() => {
    const userMsgs = chatStore.messages.filter(m => m.role === 'user');
    return userMsgs.length ? userMsgs[userMsgs.length - 1].content : '';
});

const contextSuggestions = {
    'Chief Complaint': [
        'Why did the doctor focus on this issue?',
        'Is this something I should be worried about?',
        'How is this related to my other conditions?',
        'What questions should I ask at my next visit?',
    ],
    'History of Present Illness': [
        'Can you summarize my history in simpler terms?',
        'How has my condition progressed?',
        'What factors might be causing my symptoms?',
        'What does this history mean for my treatment?',
    ],
    'Reported Symptoms': [
        'Which of these symptoms are most important?',
        'Are any of these symptoms related to each other?',
        'When should I be concerned about these symptoms?',
        'What can I do to manage these symptoms at home?',
    ],
    'Physical Examination': [
        'What did the doctor find during the exam?',
        'Are my vital signs normal?',
        'What do these physical findings mean?',
        'Should I be concerned about any of these results?',
    ],
    'Assessment': [
        'What does my diagnosis mean in simple terms?',
        'How serious is this condition?',
        'What causes this condition?',
        'What is the typical outlook for this diagnosis?',
    ],
    'Plan': [
        'Can you explain each step of my treatment plan?',
        'What happens if I miss a step in the plan?',
        'How long will this treatment take?',
        'What should I prioritize first?',
    ],
    'Follow-up': [
        'When is my next appointment?',
        'What should I prepare for the follow-up?',
        'What tests should be done before my next visit?',
        'What progress should I expect by then?',
    ],
};

const defaultSuggestions = [
    'What does my diagnosis mean in simple terms?',
    'Explain my medication and side effects',
    'What should I watch out for at home?',
    'When should I call my doctor?',
];

const suggestedQuestions = computed(() => {
    if (props.initialContext) {
        return contextSuggestions[props.initialContext] || defaultSuggestions;
    }
    return defaultSuggestions;
});

function toggleThinking(index) {
    expandedThinking[index] = !expandedThinking[index];
}

function sendQuestion(q) {
    message.value = q;
    send();
}

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

watch(() => chatStore.messages, scrollToBottom, { deep: true });

// When context changes while chat is already open, pre-fill the new context
watch(() => props.initialContext, (newCtx, oldCtx) => {
    if (newCtx && newCtx !== oldCtx) {
        message.value = `Explain: ${newCtx}`;
    }
});

onMounted(() => {
    chatStore.clearMessages();
    if (props.initialContext) {
        message.value = `Explain: ${props.initialContext}`;
    }
});
</script>
