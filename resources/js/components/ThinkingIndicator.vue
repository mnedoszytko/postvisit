<template>
  <div class="flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <div class="flex gap-1">
        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0ms" />
        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 150ms" />
        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 300ms" />
      </div>
      <span class="text-xs text-gray-400 transition-opacity duration-500">{{ currentHint }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    query: { type: String, default: '' },
});

const hintIndex = ref(0);
let interval = null;

const contextualHints = computed(() => {
    const q = props.query.toLowerCase();

    if (q.includes('medication') || q.includes('medicine') || q.includes('drug') || q.includes('side effect') || q.includes('lek')) {
        return [
            'Reviewing your prescriptions...',
            'Checking FDA safety data...',
            'Preparing medication summary...',
        ];
    }
    if (q.includes('diagnos') || q.includes('condition') || q.includes('mean')) {
        return [
            'Reviewing your diagnosis...',
            'Looking up clinical references...',
            'Writing a clear explanation...',
        ];
    }
    if (q.includes('test') || q.includes('result') || q.includes('lab') || q.includes('blood')) {
        return [
            'Reviewing your test results...',
            'Checking reference ranges...',
            'Preparing your results summary...',
        ];
    }
    if (q.includes('explain') || q.includes('what')) {
        return [
            'Reading your visit notes...',
            'Translating medical terms...',
            'Writing a clear explanation...',
        ];
    }
    if (q.includes('follow') || q.includes('next') || q.includes('watch') || q.includes('call') || q.includes('when')) {
        return [
            'Reviewing your care plan...',
            'Checking follow-up instructions...',
            'Preparing your next steps...',
        ];
    }
    return [
        'Reading your visit context...',
        'Analyzing clinical notes...',
        'Preparing your answer...',
    ];
});

const currentHint = computed(() => contextualHints.value[hintIndex.value % contextualHints.value.length]);

onMounted(() => {
    interval = setInterval(() => {
        hintIndex.value++;
    }, 2500);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>
