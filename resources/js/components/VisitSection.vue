<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <button
      class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors"
      @click="expanded = !expanded"
    >
      <div class="flex items-center gap-2">
        <span class="w-6 h-6 flex items-center justify-center rounded-lg shrink-0" :class="iconBg">
          <svg class="w-3.5 h-3.5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath" />
          </svg>
        </span>
        <h3 class="font-semibold text-gray-800">{{ title }}</h3>
      </div>
      <div class="flex items-center gap-2">
        <AskAiButton @ask="$emit('explain')" />
        <span class="text-gray-400 text-sm">{{ expanded ? 'Collapse' : 'Expand' }}</span>
      </div>
    </button>

    <div v-if="expanded" class="px-4 pb-4 space-y-3">
      <HighlightedText
        v-if="terms?.length"
        :content="content"
        :terms="terms"
        :section-key="sectionKey"
        @term-click="(payload) => $emit('term-click', payload)"
      />
      <div v-else class="prose prose-sm max-w-none text-gray-700" v-html="renderContent(content)" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { safeMarkdown } from '@/utils/sanitize';
import HighlightedText from '@/components/HighlightedText.vue';
import AskAiButton from '@/components/AskAiButton.vue';

function renderContent(text) {
    if (!text) return '';
    return safeMarkdown(text);
}

const props = defineProps({
    title: { type: String, required: true },
    content: { type: String, default: '' },
    terms: { type: Array, default: () => [] },
    sectionKey: { type: String, default: '' },
});

defineEmits(['explain', 'term-click']);

const expanded = ref(false);

const sectionIcons = {
    chief_complaint: {
        path: 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
        bg: 'bg-emerald-50',
        color: 'text-emerald-600',
    },
    history_of_present_illness: {
        path: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        bg: 'bg-emerald-50',
        color: 'text-emerald-600',
    },
    review_of_systems: {
        path: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
        bg: 'bg-emerald-50',
        color: 'text-emerald-600',
    },
    physical_exam: {
        path: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z',
        bg: 'bg-emerald-50',
        color: 'text-emerald-600',
    },
    assessment: {
        path: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5',
        bg: 'bg-emerald-50',
        color: 'text-emerald-600',
    },
    plan: {
        path: 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
        bg: 'bg-emerald-50',
        color: 'text-emerald-600',
    },
    follow_up: {
        path: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        bg: 'bg-emerald-50',
        color: 'text-emerald-600',
    },
};

const defaultIcon = {
    path: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    bg: 'bg-gray-50',
    color: 'text-gray-400',
};

const icon = computed(() => sectionIcons[props.sectionKey] || defaultIcon);
const iconPath = computed(() => icon.value.path);
const iconBg = computed(() => icon.value.bg);
const iconColor = computed(() => icon.value.color);
</script>
