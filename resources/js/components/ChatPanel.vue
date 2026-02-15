<template>
  <div
    :class="[
      'bg-white flex flex-col transition-all duration-500 relative',
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
          <img src="/images/logo-icon.png" alt="" class="h-4 w-auto" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 text-sm">PostVisit AI</h3>
          <p v-if="chatStore.loading" class="text-[10px] text-emerald-600 font-medium">Reviewing your visit...</p>
          <p v-else class="text-[10px] text-gray-400">Ask anything about your visit</p>
        </div>
      </div>
      <div class="flex items-center gap-1">
        <button
          v-if="embedded"
          class="text-gray-400 hover:text-emerald-600 transition-colors p-1 rounded-lg hover:bg-emerald-50"
          :title="maximized ? 'Restore chat size' : 'Maximize chat'"
          @click="$emit('toggle-maximize')"
        >
          <!-- Maximize icon -->
          <svg v-if="!maximized" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
          </svg>
          <!-- Restore icon -->
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
          </svg>
        </button>
        <button
          class="text-gray-400 hover:text-gray-600 transition-colors text-xl leading-none p-1"
          @click="$emit('close')"
        >
          &times;
        </button>
      </div>
    </div>

    <!-- Welcome message when empty -->
    <div v-if="!chatStore.messages.length && !chatStore.loading" class="flex-1 flex flex-col items-center p-6 pt-12 text-center overflow-y-auto">
      <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mb-4">
        <img src="/images/logo-icon.png" alt="" class="h-8 w-auto" />
      </div>
      <h4 class="font-semibold text-gray-800 mb-1">Your visit assistant</h4>
      <p v-if="initialContext" class="text-xs text-emerald-600 font-medium mb-2 px-3 py-1 bg-emerald-50 rounded-full inline-block capitalize">
        {{ suggestionLabel }}
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
        <!-- Context pill above user bubble -->
        <div v-if="msg.role === 'user' && extractContext(msg.content)" class="flex justify-end mb-1">
          <span class="inline-flex items-center gap-0.5 text-[10px] font-medium text-emerald-600 bg-emerald-50 rounded-full px-2 py-0.5">
            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
            </svg>
            {{ extractContext(msg.content) }}
          </span>
        </div>
        <div
          :class="[
            'rounded-2xl px-4 py-3 text-sm',
            msg.role === 'user'
              ? 'bg-emerald-600 text-white'
              : 'bg-emerald-50 text-gray-800 border border-emerald-100'
          ]"
        >
          <!-- Thinking indicator (before content arrives) -->
          <ThinkingIndicator
            v-if="msg.streaming && !msg.content"
            :query="lastUserMessage"
            :thinking-active="msg.thinkingPhase"
          />

          <!-- Streaming response -->
          <StreamingMessage
            v-else-if="msg.streaming && msg.content"
            :text="stripSources(msg.content)"
          />

          <!-- Completed assistant message -->
          <div v-else-if="msg.role === 'assistant'" class="chat-prose prose prose-sm max-w-none" v-html="renderMarkdown(stripSources(msg.content))" />

          <!-- User message -->
          <p v-else>{{ stripContext(msg.content) }}</p>
        </div>
        <!-- Source chips for completed assistant messages -->
        <SourceChips
          v-if="msg.role === 'assistant' && !msg.streaming && parseSources(msg.content).length"
          :sources="parseSources(msg.content)"
          class="mt-1.5"
          @source-click="handleSourceClick"
        />
        <!-- Powered by badge + effort indicator + share actions for completed assistant messages -->
        <div
          v-if="msg.role === 'assistant' && !msg.streaming && msg.content"
          class="mt-1.5 flex items-center gap-2"
        >
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[9px] font-medium border border-emerald-200/50">
            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
            </svg>
            Powered by Opus 4.6
          </span>
          <!-- Effort level badge (skip medium — it's the default, don't clutter) -->
          <span
            v-if="msg.effort === 'low'"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-medium bg-gray-100 text-gray-500 border border-gray-200/50"
            title="Simple factual question — minimal thinking budget"
          >
            Quick answer
          </span>
          <span
            v-else-if="msg.effort === 'high'"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-medium bg-amber-50 text-amber-600 border border-amber-200/50"
            title="Drug safety or interaction question — deep thinking budget"
          >
            Deep analysis
          </span>
          <span
            v-else-if="msg.effort === 'max'"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-medium bg-red-50 text-red-600 border border-red-200/50"
            title="Safety-critical question — maximum thinking budget"
          >
            Clinical reasoning
          </span>
          <div class="flex items-center gap-0.5 ml-auto">
            <!-- Copy -->
            <button
              class="p-1 rounded text-gray-300 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
              title="Copy response"
              @click="copyResponse(msg)"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
              </svg>
            </button>
            <!-- Print -->
            <button
              class="p-1 rounded text-gray-300 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
              title="Print response"
              @click="printResponse(msg)"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
              </svg>
            </button>
            <!-- Share (native) -->
            <button
              v-if="canShare"
              class="p-1 rounded text-gray-300 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
              title="Share response"
              @click="shareResponse(msg)"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Inline suggestions when context changes mid-conversation -->
      <div v-if="inlineSuggestions.length" class="mt-2 pt-3 border-t border-gray-100">
        <p v-if="initialContext" class="text-[10px] text-emerald-600 font-medium mb-2 px-1">
          {{ suggestionLabel }}
        </p>
        <div class="space-y-1.5">
          <button
            v-for="q in inlineSuggestions"
            :key="q"
            class="w-full text-left text-xs px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all"
            @click="sendQuestion(q)"
          >
            {{ q }}
          </button>
        </div>
      </div>
    </div>

    <!-- Copy toast -->
    <Transition name="fade">
      <div v-if="copyToast" class="absolute bottom-16 left-1/2 -translate-x-1/2 z-20 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-lg shadow-lg">
        {{ copyToast }}
      </div>
    </Transition>

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
            class="absolute bottom-12 left-0 w-60 bg-white rounded-xl shadow-lg border border-gray-200 p-3 z-50"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-semibold text-gray-700">Context Sources</span>
              <span class="text-[10px] text-gray-400">{{ selectedSources.length }}/{{ contextSources.length }}</span>
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
              </label>
            </div>
            <div class="mt-2 pt-2 border-t border-gray-100 flex justify-between">
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
import { useRouter } from 'vue-router';
import { useChatStore } from '@/stores/chat';
import { useVisitStore } from '@/stores/visit';
import { safeMarkdown } from '@/utils/sanitize';
import StreamingMessage from '@/components/StreamingMessage.vue';
import ThinkingIndicator from '@/components/ThinkingIndicator.vue';
import SourceChips from '@/components/SourceChips.vue';

function renderMarkdown(text) {
    return safeMarkdown(text || '');
}

function stripSources(text) {
    if (!text) return '';
    return text.replace(/\[sources\][\s\S]*?\[\/sources\]/g, '').trim();
}

function extractContext(text) {
    if (!text) return '';
    const match = text.match(/^\[Context:\s*(.*?)\]\s*/);
    return match ? match[1] : '';
}

function stripContext(text) {
    if (!text) return text;
    return text.replace(/^\[Context:\s*.*?\]\s*\n*/, '').trim();
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
            // Add visit date to practitioner badge for clarity
            if (key === 'practitioner' && visitStore.currentVisit?.started_at) {
                const date = new Date(visitStore.currentVisit.started_at);
                const formatted = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                return { label: `${label} · ${formatted}`, key };
            }
            return { label, key };
        });
}

const props = defineProps({
    visitId: { type: String, required: true },
    initialContext: { type: String, default: '' },
    highlight: { type: Boolean, default: false },
    embedded: { type: Boolean, default: false },
    maximized: { type: Boolean, default: false },
    contextKey: { type: Number, default: 0 },
});

defineEmits(['close', 'toggle-maximize']);

const router = useRouter();
const chatStore = useChatStore();
const visitStore = useVisitStore();
const message = ref('');
const messagesContainer = ref(null);
const showContextMenu = ref(false);
const sendButton = ref(null);
const sendGlow = ref(false);
const inlineSuggestions = ref([]);
const pendingContext = ref('');

const contextSources = reactive([
    { id: 'visit', label: 'Visit Notes', icon: '📋', description: 'SOAP notes, transcript', selected: true },
    { id: 'health', label: 'Health Data', icon: '❤️', description: 'Biometrics, vitals, labs, device data', selected: true },
    { id: 'medications', label: 'Medications', icon: '💊', description: 'Prescriptions, drug info', selected: true },
    { id: 'references', label: 'Medical References', icon: '📚', description: 'Guidelines, conditions', selected: true },
    { id: 'documents', label: 'Documents', icon: '📄', description: 'Uploaded files, reports', selected: false },
]);

// Map context keys to which sources should be prioritized (selected)
const contextSourcePresets = {
    '': ['visit', 'health', 'medications', 'references'], // General — all main
    'visit': ['visit', 'health', 'medications'],
    'health': ['health', 'medications', 'documents'],
    'health record': ['health', 'medications', 'documents'],
    'vitals': ['health'],
    'lab': ['health', 'documents'],
    'apple watch': ['health'],
    'reference': ['references', 'medications'],
    'condition:': ['references', 'medications', 'health'],
    'medication:': ['medications', 'references'],
    'document:': ['documents', 'health'],
    // SOAP sections → visit focused
    'chief_complaint': ['visit'],
    'history_of_present_illness': ['visit'],
    'review_of_systems': ['visit', 'health'],
    'physical_exam': ['visit', 'health'],
    'assessment': ['visit', 'references'],
    'plan': ['visit', 'medications', 'references'],
    'follow_up': ['visit', 'medications'],
};

function applySourcePreset(context) {
    const lower = (context || '').toLowerCase();
    // Find matching preset: exact → prefix → fallback to all
    let preset = contextSourcePresets[lower];
    if (!preset) {
        for (const key of Object.keys(contextSourcePresets)) {
            if (key.endsWith(':') && lower.startsWith(key)) {
                preset = contextSourcePresets[key];
                break;
            }
        }
    }
    if (!preset) preset = contextSourcePresets[''];
    contextSources.forEach(s => { s.selected = preset.includes(s.id); });
}

const selectedSources = computed(() => contextSources.filter(s => s.selected));

function selectAllSources() {
    contextSources.forEach(s => s.selected = true);
}

const lastUserMessage = computed(() => {
    const userMsgs = chatStore.messages.filter(m => m.role === 'user');
    return userMsgs.length ? userMsgs[userMsgs.length - 1].content : '';
});

const contextSuggestions = {
    // Visit-level context (default when entering a visit)
    'visit': [
        'Summarize this visit for me in simple terms',
        'What are the key takeaways from this visit?',
        'What should I do before my next appointment?',
        'Explain my diagnosis and treatment plan',
        'What medications were prescribed and why?',
        'What warning signs should I watch for?',
        'What questions should I ask at my follow-up?',
    ],
    'Chief Complaint': [
        'Why did the doctor focus on this issue?',
        'Is this something I should be worried about?',
        'How is this related to my other conditions?',
        'What questions should I ask at my next visit?',
        'What could happen if this issue is not treated?',
        'How common is this complaint?',
    ],
    'History of Present Illness': [
        'Can you summarize my history in simpler terms?',
        'How has my condition progressed?',
        'What factors might be causing my symptoms?',
        'What does this history mean for my treatment?',
        'Has anything in my history changed since my last visit?',
        'What patterns should I be aware of?',
    ],
    'Reported Symptoms': [
        'Which of these symptoms are most important?',
        'Are any of these symptoms related to each other?',
        'When should I be concerned about these symptoms?',
        'What can I do to manage these symptoms at home?',
        'Are these symptoms typical for my condition?',
        'Should I keep a symptom diary?',
    ],
    'Physical Examination': [
        'What did the doctor find during the exam?',
        'Are my vital signs normal?',
        'What do these physical findings mean?',
        'Should I be concerned about any of these results?',
        'How do my results compare to the last exam?',
        'What does the doctor look for during this type of exam?',
    ],
    'Assessment': [
        'What does my diagnosis mean in simple terms?',
        'How serious is this condition?',
        'What causes this condition?',
        'What is the typical outlook for this diagnosis?',
        'Are there different stages of this condition?',
        'How will this condition affect my daily life?',
    ],
    'Plan': [
        'Can you explain each step of my treatment plan?',
        'What happens if I miss a step in the plan?',
        'How long will this treatment take?',
        'What should I prioritize first?',
        'Are there alternative treatment options?',
        'What lifestyle changes does this plan require?',
    ],
    'Follow-up': [
        'When is my next appointment?',
        'What should I prepare for the follow-up?',
        'What tests should be done before my next visit?',
        'What progress should I expect by then?',
        'Can I reschedule if I feel better?',
        'What should I track between now and the follow-up?',
    ],
    "Doctor's Recommendations": [
        'Why did the doctor recommend this treatment?',
        'What happens if I don\'t follow these recommendations?',
        'How soon should I start these changes?',
        'Are there alternatives to the recommended treatment?',
        'Which recommendation is the most important?',
        'Can you explain each recommendation in simple terms?',
    ],
    'Next Actions': [
        'What should I do first from this list?',
        'How do I schedule the follow-up tests?',
        'What medications do I need to start taking?',
        'Are there any time-sensitive actions I need to take?',
        'What can I start doing today?',
        'How do I track my progress on these actions?',
    ],
    // Health Profile sections (matched by keyword)
    'biometrics': [
        'What does my BMI mean?',
        'Is my weight healthy for my height?',
        'What should my target weight be?',
        'How does my blood type affect my health?',
        'How are my biometrics trending over time?',
        'What biometric targets should I aim for?',
    ],
    'diagnos': [
        'What does my diagnosis mean in simple terms?',
        'How serious is this condition?',
        'What lifestyle changes should I make?',
        'What are the treatment options?',
        'How is this condition typically monitored?',
        'What are the early warning signs of worsening?',
    ],
    'allerg': [
        'What should I avoid with my allergies?',
        'What are the symptoms of an allergic reaction?',
        'Should I carry an EpiPen?',
        'Can my allergies change over time?',
        'How do I manage allergies when traveling?',
        'Are there any cross-reactivities I should know about?',
    ],
    'visit': [
        'What happened during my last visit?',
        'What was discussed?',
        'What was the conclusion?',
        'Summarize my last visit',
        'Give me the most important points',
        'What follow-up actions were recommended?',
    ],
    'medication': [
        'Explain my medication and side effects',
        'Can I drink alcohol with my medication?',
        'What happens if I miss a dose?',
        'Are there any food interactions?',
        'How long do I need to take this medication?',
        'What should I do if I experience side effects?',
    ],
    // Vitals tab sections
    'blood pressure': [
        'Is my blood pressure normal?',
        'What do my BP trends show?',
        'Should I be concerned about my blood pressure readings?',
        'How can I improve my blood pressure?',
        'What foods help lower blood pressure?',
        'When should I measure my blood pressure at home?',
    ],
    'heart rate': [
        'Is my heart rate healthy?',
        'What do my heart rate trends mean?',
        'Should I be concerned about my resting heart rate?',
        'What affects heart rate?',
        'What is a normal heart rate range for my age?',
        'How does exercise affect my heart rate trends?',
    ],
    'heart rate variability': [
        'What does my HRV data mean?',
        'Is my HRV normal for my age?',
        'How can I improve my HRV?',
        'What does low HRV indicate?',
        'How does stress affect my HRV?',
        'What lifestyle changes improve HRV?',
    ],
    'weight': [
        'Is my weight trend concerning?',
        'What does my weight change mean?',
        'Is my current BMI healthy?',
        'What should my target weight be?',
        'How fast should I lose or gain weight?',
        'What factors besides diet affect my weight?',
    ],
    'sleep': [
        'Am I getting enough sleep?',
        'What do my sleep patterns show?',
        'How can I improve my sleep quality?',
        'Is my deep sleep duration normal?',
        'How does sleep affect my condition?',
        'What sleep hygiene habits should I follow?',
    ],
    'apple watch': [
        'What does my Apple Watch data show?',
        'Are there any concerning patterns in my device data?',
        'How do my step counts compare to recommendations?',
        'What do my SpO2 readings mean?',
        'Should I share this device data with my doctor?',
        'What health metrics should I monitor daily?',
    ],
    // Health dashboard / patient record
    'health': [
        'Give me an overview of my health record',
        'What are the key things in my medical history?',
        'Are there any concerning trends in my health data?',
        'What lab results should I pay attention to?',
        'Summarize my current conditions and medications',
        'What preventive screenings am I due for?',
        'How has my health changed over time?',
    ],
    // Lab results
    'lab': [
        'Are my lab results normal?',
        'What do my lab trends show?',
        'Which results should I be concerned about?',
        'Explain my cholesterol levels',
        'How often should these labs be repeated?',
        'What can I do to improve my results?',
    ],
    // Clinical references (MH-13)
    'reference': [
        'What clinical guidelines apply to my condition?',
        'Is this treatment supported by current evidence?',
        'What do the latest studies say about my diagnosis?',
        'Are there newer treatment approaches available?',
        'What professional organizations published these guidelines?',
        'How often are these guidelines updated?',
        'Where can I find reliable information about my condition?',
    ],
    // Prefix-matched: condition-specific (MH-13)
    'condition:': [
        'What are the treatment guidelines for this condition?',
        'What lifestyle changes help manage this condition?',
        'How is this condition monitored over time?',
        'Are there clinical trials for this condition?',
        'What is the long-term outlook for this condition?',
        'What are the most common complications?',
        'When should I seek emergency care?',
    ],
    // Prefix-matched: medication-specific (MH-13)
    'medication:': [
        'What are the common side effects of this medication?',
        'Are there known drug interactions I should watch for?',
        'What is the recommended dosage schedule?',
        'When should I contact my doctor about this medication?',
        'How long until this medication takes effect?',
        'Can I take this medication with food?',
        'What happens if I stop taking this medication suddenly?',
    ],
    // Prefix-matched: lab result specific
    'lab:': [
        'Is my result normal?',
        'What could cause an abnormal result?',
        'How does this compare to my previous results?',
        'What lifestyle changes can improve this value?',
        'When should this test be repeated?',
        'What does this lab test measure?',
        'Should I be concerned about my result?',
    ],
};

const defaultSuggestions = [
    'What does my diagnosis mean in simple terms?',
    'Explain my medication and side effects',
    'What should I watch out for at home?',
    'When should I call my doctor?',
    'Can I drink alcohol with my medication?',
    'What lifestyle changes should I make?',
    'What questions should I ask at my next visit?',
];

function pickSuggestions(pool, count = 3) {
    return pool.slice(0, count);
}

// Aliases for context matching (e.g., "health record" → "health")
const contextAliases = {
    'health record': 'health',
    'patient record': 'health',
    'my health': 'health',
    'medical library': 'reference',
    'library': 'reference',
};

function findSuggestionsForContext(context) {
    if (!context) return { pool: defaultSuggestions, isDefault: true };

    const lower = context.toLowerCase();

    // Check aliases first
    const aliased = contextAliases[lower];
    if (aliased && contextSuggestions[aliased]) {
        return { pool: contextSuggestions[aliased], isDefault: false };
    }

    // Exact match (SOAP sections from VisitView, 'reference', 'health', etc.)
    if (contextSuggestions[context]) {
        return { pool: contextSuggestions[context], isDefault: false };
    }
    // Case-insensitive exact match
    if (contextSuggestions[lower]) {
        return { pool: contextSuggestions[lower], isDefault: false };
    }

    // Prefix match for 'condition:', 'medication:', 'document:' keys
    for (const key of ['condition:', 'medication:', 'document:', 'lab:']) {
        if (lower.startsWith(key)) {
            return { pool: contextSuggestions[key] || defaultSuggestions, isDefault: false };
        }
    }

    // Keyword match (Health Profile sections, vitals, etc.)
    for (const [key, suggestions] of Object.entries(contextSuggestions)) {
        if (key.endsWith(':')) continue; // skip prefix keys in keyword match
        if (lower.includes(key.toLowerCase())) {
            return { pool: suggestions, isDefault: false };
        }
    }

    return { pool: defaultSuggestions, isDefault: true };
}

const suggestionSource = computed(() => findSuggestionsForContext(props.initialContext));
const suggestedQuestions = ref([]);

function refreshSuggestions() {
    suggestedQuestions.value = pickSuggestions(suggestionSource.value.pool, 3);
}
const contextLabelMap = {
    'visit': 'Visit Summary',
    'health': 'Patient Record',
    'health record': 'Patient Record',
    'reference': 'Reference',
    'lab': 'Lab Results',
    'vitals': 'Vitals',
    'sleep': 'Sleep',
    'weight': 'Weight',
    'heart_rate': 'Heart Rate',
    'hrv': 'HRV',
    'apple watch': 'Apple Watch',
    "doctor's recommendations": 'Recommendations',
    'next actions': 'Next Actions',
};

const suggestionLabel = computed(() => {
    if (suggestionSource.value.isDefault) return 'General';
    const ctx = (props.initialContext || '').toLowerCase();
    // Check label map first
    if (contextLabelMap[ctx]) return contextLabelMap[ctx];
    // Prefix match: "condition: X" → "X", "medication: Y" → "Y"
    for (const prefix of ['condition:', 'medication:', 'document:']) {
        if (ctx.startsWith(prefix)) {
            return props.initialContext.slice(prefix.length).trim();
        }
    }
    // SOAP sections
    const soapLabels = {
        'chief_complaint': 'Chief Complaint',
        'history_of_present_illness': 'History',
        'review_of_systems': 'Review of Systems',
        'physical_exam': 'Physical Exam',
        'assessment': 'Assessment',
        'plan': 'Plan',
        'follow_up': 'Follow-up',
    };
    if (soapLabels[ctx]) return soapLabels[ctx];
    // Fallback: use the raw context but capitalize
    return props.initialContext || 'General';
});

function handleSourceClick(source) {
    const actions = {
        visit_notes: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        practitioner: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        openfda: () => window.open('https://open.fda.gov', '_blank'),
        guidelines: () => router.push({ name: 'medical-library' }),
        patient_record: () => router.push({ name: 'health-dashboard' }),
    };
    const action = actions[source.key];
    if (action) action();
}

function sendQuestion(q) {
    message.value = q;
    send();
}

async function send() {
    if (!message.value.trim()) return;
    let text = message.value;
    message.value = '';
    showContextMenu.value = false;
    inlineSuggestions.value = [];

    // Inject pending context into the message so the AI knows what we're asking about
    if (pendingContext.value) {
        text = `[Context: ${pendingContext.value}]\n\n${text}`;
        pendingContext.value = '';
    }

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

watch(() => chatStore.messages.length, () => {
    scrollToBottom();
});

function triggerSendGlow() {
    sendGlow.value = false;
    nextTick(() => {
        sendGlow.value = true;
        setTimeout(() => { sendGlow.value = false; }, 1600);
    });
}

// When context changes while chat is already open, sync sources + suggestions
// Also watch contextKey so repeated clicks on the same section re-trigger
watch([() => props.initialContext, () => props.contextKey], ([newCtx]) => {
    if (newCtx) {
        pendingContext.value = newCtx;
        applySourcePreset(newCtx);

        if (chatStore.messages.length > 0) {
            const { pool } = findSuggestionsForContext(newCtx);
            inlineSuggestions.value = pickSuggestions(pool, 3);
            message.value = '';
            scrollToBottom();
        }
    }
    // Always refresh welcome screen suggestions (single update, no reactive loop)
    refreshSuggestions();
});

// --- Share actions ---
const copyToast = ref('');
const canShare = typeof navigator !== 'undefined' && !!navigator.share;

function getPlainText(msg) {
    return stripSources(msg.content);
}

async function copyResponse(msg) {
    try {
        await navigator.clipboard.writeText(getPlainText(msg));
        copyToast.value = 'Copied!';
        setTimeout(() => { copyToast.value = ''; }, 2000);
    } catch {
        // fallback: select nothing
    }
}

function printResponse(msg) {
    const html = renderMarkdown(getPlainText(msg));
    const win = window.open('', '_blank', 'width=700,height=900');
    if (!win) return;
    win.document.write(`<!DOCTYPE html><html><head><title>PostVisit AI Response</title>
<style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;max-width:640px;margin:40px auto;padding:0 20px;color:#1f2937;line-height:1.6}
h1,h2,h3{margin-top:1.5em}ul,ol{padding-left:1.5em}code{background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:0.9em}
.footer{margin-top:3em;padding-top:1em;border-top:1px solid #e5e7eb;font-size:0.75em;color:#9ca3af;text-align:center}</style></head>
<body>${html}<div class="footer">Generated by PostVisit AI &middot; Powered by Claude Opus 4.6<br>This is an AI-generated summary and should not replace professional medical advice.</div></body></html>`);
    win.document.close();
    win.print();
}

async function shareResponse(msg) {
    try {
        await navigator.share({
            title: 'PostVisit AI Analysis',
            text: getPlainText(msg),
        });
    } catch {
        // user cancelled or not supported
    }
}

onMounted(() => {
    chatStore.clearMessages();
    if (props.initialContext) {
        pendingContext.value = props.initialContext;
    }
    applySourcePreset(props.initialContext);
    refreshSuggestions();
});
</script>
