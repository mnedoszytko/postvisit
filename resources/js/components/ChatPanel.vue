<template>
  <div
    :class="[
      'bg-white flex flex-col transition-all duration-500',
      embedded
        ? 'w-full h-full rounded-2xl border shadow-sm'
        : 'fixed inset-y-0 right-0 w-full sm:w-96 border-l shadow-xl z-50',
      highlight ? 'border-emerald-400 shadow-lg shadow-emerald-200/60 ring-2 ring-emerald-300 animate-[chat-flash_1.5s_ease-out]' : 'border-gray-200'
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
    <div v-if="!chatStore.messages.length && !chatStore.loading" class="flex-1 flex flex-col items-center p-6 pt-12 text-center overflow-y-auto">
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
          <!-- Phase 1: Initial thinking (before quick answer arrives) -->
          <ThinkingIndicator
            v-if="msg.streaming && !msg.quickContent && !msg.content"
            :query="lastUserMessage"
            :thinking-active="msg.thinkingPhase"
          />

          <!-- Phase 2: Quick answer streaming / displayed -->
          <div v-else-if="msg.streaming && msg.quickContent && !msg.content">
            <div class="flex items-center gap-1.5 mb-2">
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-medium">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
                Let me think...
              </span>
            </div>
            <p class="text-sm leading-relaxed">{{ msg.quickContent }}</p>

            <div v-if="msg.quickDone" class="mt-2 pt-2 border-t border-gray-200/60">
              <DeepAnalysisIndicator
                :status-text="msg.statusText"
                :thinking="msg.thinking"
                :thinking-active="msg.thinkingPhase"
              />
            </div>
          </div>

          <!-- Phase 3: Opus answer streaming (deep analysis in progress) -->
          <div v-else-if="msg.streaming && msg.content">
            <!-- Keep quick answer visible above -->
            <div v-if="msg.quickContent" class="mb-3">
              <div class="flex items-center gap-1.5 mb-1">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-200/60 text-gray-500 text-[10px] font-medium">
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                  </svg>
                  Let me think...
                </span>
              </div>
              <p class="text-xs text-gray-500 leading-relaxed">{{ msg.quickContent }}</p>
            </div>
            <!-- Opus answer on amber background -->
            <div class="bg-amber-50/80 border border-amber-200/60 rounded-xl px-3 py-2.5 -mx-1">
              <div class="flex items-center gap-1.5 mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-medium">
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                  </svg>
                  Detailed clinical analysis
                </span>
              </div>
              <StreamingMessage :text="stripSources(msg.content)" />
            </div>
          </div>

          <!-- Phase 4: Completed message — keep quick + opus visible -->
          <div v-else-if="msg.role === 'assistant'">
            <!-- Quick answer stays visible (faded) -->
            <div v-if="msg.quickContent" class="mb-3">
              <div class="flex items-center gap-1.5 mb-1">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-200/60 text-gray-500 text-[10px] font-medium">
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                  </svg>
                  Let me think...
                </span>
              </div>
              <p class="text-xs text-gray-500 leading-relaxed">{{ msg.quickContent }}</p>
            </div>
            <!-- Full Opus answer on amber background -->
            <div :class="msg.quickContent ? 'bg-amber-50/80 border border-amber-200/60 rounded-xl px-3 py-2.5 -mx-1' : ''">
              <div v-if="msg.quickContent" class="flex items-center gap-1.5 mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-medium">
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                  </svg>
                  Detailed clinical analysis
                </span>
              </div>
              <div class="prose prose-sm max-w-none" v-html="renderMarkdown(stripSources(msg.content))" />
            </div>
          </div>

          <!-- User message -->
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
    <form class="border-t border-gray-200 p-3 flex items-end gap-2 shrink-0" @submit.prevent="send">
      <!-- Context selector (+) button -->
      <div class="relative">
        <button
          type="button"
          class="w-9 h-9 rounded-full flex items-center justify-center transition-all shrink-0"
          :class="showContextMenu
            ? 'bg-emerald-100 text-emerald-700 ring-2 ring-emerald-300'
            : 'bg-gray-100 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600'"
          @click="showContextMenu = !showContextMenu"
          title="Select context sources"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
        </button>

        <!-- Context menu popover -->
        <Transition
          enter-active-class="transition ease-out duration-150"
          enter-from-class="opacity-0 translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-2"
        >
          <div
            v-if="showContextMenu"
            class="absolute bottom-12 left-0 w-64 bg-white rounded-xl shadow-lg border border-gray-200 p-3 z-50"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-semibold text-gray-700">Context Sources</span>
              <span class="text-[10px] text-gray-400 font-mono">{{ contextTokenEstimate }}</span>
            </div>
            <div class="space-y-1">
              <label
                v-for="src in contextSources"
                :key="src.id"
                class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg cursor-pointer transition-colors"
                :class="src.selected ? 'bg-emerald-50' : 'hover:bg-gray-50'"
              >
                <input
                  type="checkbox"
                  v-model="src.selected"
                  class="w-3.5 h-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                />
                <span class="text-base leading-none">{{ src.icon }}</span>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-gray-800">{{ src.label }}</div>
                  <div class="text-[10px] text-gray-400">{{ src.description }}</div>
                </div>
                <span class="text-[10px] text-gray-400 font-mono shrink-0">{{ src.tokens }}</span>
              </label>
            </div>
            <!-- Token usage bar -->
            <div class="mt-2.5 pt-2 border-t border-gray-100">
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] text-gray-500">Context loaded</span>
                <span class="text-[10px] font-mono font-medium" :class="contextPercentage > 50 ? 'text-emerald-600' : 'text-gray-400'">
                  {{ contextTokenEstimate }} / 1M tokens
                </span>
              </div>
              <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500 ease-out"
                  :class="contextPercentage > 50 ? 'bg-emerald-500' : 'bg-emerald-400'"
                  :style="{ width: Math.max(contextPercentage, 1) + '%' }"
                ></div>
              </div>
              <p class="text-[9px] text-gray-400 mt-1">Powered by Claude Opus 4.6 — 1M token context window</p>
            </div>
            <div class="mt-2 flex justify-between">
              <button
                type="button"
                class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium"
                @click="selectAllSources"
              >
                Select All
              </button>
              <button
                type="button"
                class="text-[11px] text-gray-500 hover:text-gray-700 font-medium"
                @click="showContextMenu = false"
              >
                Done
              </button>
            </div>
          </div>
        </Transition>
      </div>

      <input
        v-model="message"
        type="text"
        placeholder="Ask about your visit..."
        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
        :disabled="chatStore.loading"
      />
      <button
        ref="sendButton"
        type="submit"
        :disabled="!message.trim() || chatStore.loading"
        :class="[
          'px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shrink-0',
          sendGlow ? 'animate-send-glow' : ''
        ]"
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
import DeepAnalysisIndicator from '@/components/DeepAnalysisIndicator.vue';

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
    embedded: { type: Boolean, default: false },
});

defineEmits(['close']);

const chatStore = useChatStore();
const message = ref('');
const messagesContainer = ref(null);
const expandedThinking = reactive({});
const showContextMenu = ref(false);
const sendButton = ref(null);
const sendGlow = ref(false);

const contextSources = reactive([
    { id: 'visit', label: 'Visit Notes', shortLabel: 'Visit', icon: '📋', description: 'SOAP notes, transcript', tokens: '~12K', selected: true },
    { id: 'health', label: 'Health Data', shortLabel: 'Health', icon: '❤️', description: 'Vitals, observations, labs', tokens: '~8K', selected: true },
    { id: 'medications', label: 'Medications', shortLabel: 'Meds', icon: '💊', description: 'Prescriptions, FDA data', tokens: '~6K', selected: true },
    { id: 'references', label: 'Medical References', shortLabel: 'Refs', icon: '📚', description: 'Guidelines, conditions', tokens: '~45K', selected: true },
    { id: 'documents', label: 'Documents', shortLabel: 'Docs', icon: '📄', description: 'Uploaded files, reports', tokens: '~15K', selected: false },
]);

const selectedSources = computed(() => contextSources.filter(s => s.selected));

const contextTokensRaw = computed(() => {
    return selectedSources.value.reduce((sum, s) => {
        const num = parseInt(s.tokens.replace(/[^0-9]/g, ''));
        return sum + (num * 1000);
    }, 0);
});

const contextTokenEstimate = computed(() => {
    const total = contextTokensRaw.value;
    if (total >= 1000000) return `${(total / 1000000).toFixed(1)}M`;
    return `${Math.round(total / 1000)}K`;
});

const contextPercentage = computed(() => {
    return (contextTokensRaw.value / 1000000) * 100;
});

function selectAllSources() {
    contextSources.forEach(s => s.selected = true);
}

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
    'Can I drink alcohol with my medication?',
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
    showContextMenu.value = false;
    const sources = selectedSources.value.map(s => s.id);
    await chatStore.sendMessage(props.visitId, text, sources);
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

function triggerSendGlow() {
    sendGlow.value = false;
    nextTick(() => {
        sendGlow.value = true;
        setTimeout(() => { sendGlow.value = false; }, 1600);
    });
}

// When context changes while chat is already open, pre-fill the new context
watch(() => props.initialContext, (newCtx) => {
    if (newCtx) {
        message.value = `Explain: ${newCtx}`;
        triggerSendGlow();
    }
});

onMounted(() => {
    chatStore.clearMessages();
    if (props.initialContext) {
        message.value = `Explain: ${props.initialContext}`;
        triggerSendGlow();
    }
});
</script>
